<?php
declare(strict_types=1);

final class AgroAssistant
{
    private array $knowledgeBase;
    private array $experts;
    private AgroRagEngine $rag;

    private array $stopWords = [
        'a', 'about', 'after', 'all', 'and', 'any', 'are', 'at', 'be', 'been', 'before', 'can', 'could',
        'did', 'do', 'for', 'from', 'get', 'have', 'how', 'i', 'if', 'in', 'into', 'is', 'it', 'my',
        'of', 'on', 'or', 'please', 'should', 'so', 'that', 'the', 'their', 'there', 'this', 'to',
        'was', 'what', 'when', 'why', 'with', 'would', 'your',
    ];

    public function __construct(array $knowledgeBase, array $experts = [])
    {
        $this->knowledgeBase = $knowledgeBase;
        $this->experts = $experts;
        $this->rag = new AgroRagEngine($knowledgeBase);
    }

    public function answer(string $message, array $context = []): array
    {
        $cleanMessage = trim($message);
        $retrieval = $this->rag->retrieve($cleanMessage, 4);
        $topMatches = $retrieval['documents'];
        $category = $context['category'] ?? $this->detectCategory($cleanMessage, $topMatches);
        $crop = $context['crop'] ?? $this->detectCrop($cleanMessage, $topMatches);
        $intent = $this->detectIntent($cleanMessage);
        $actions = $this->buildActionList($topMatches);
        $confidence = $this->scoreConfidence($cleanMessage, $topMatches, $context, $intent);
        $route = $this->selectRoute($cleanMessage, $intent, $confidence['label']);
        $needsExpert = $route === 'ESCALATE';
        $references = [];

        foreach ($retrieval['chunks'] as $chunk) {
            $reference = (string) ($chunk['title'] ?? 'Untitled entry') . ' - ' . (string) ($chunk['source'] ?? 'WeAgri knowledge base');
            if (!in_array($reference, $references, true)) {
                $references[] = $reference;
            }
        }

        return [
            'reply' => $this->composeReply($topMatches, $actions, $route, $category, $crop, $intent, $cleanMessage, $confidence['label']),
            'references' => $references,
            'actions' => $actions,
            'category' => $category,
            'crop' => $crop,
            'intent' => $intent,
            'route' => $route,
            'confidence' => $confidence['score'],
            'confidence_label' => $confidence['label'],
            'retrieval' => [
                'mode' => $topMatches === [] ? 'GENERAL_KNOWLEDGE_FALLBACK' : 'CHUNKED_LOCAL_RAG',
                'top_score' => $topMatches[0]['score'] ?? 0,
                'documents_used' => count($topMatches),
                'chunks_used' => count($retrieval['chunks']),
                'grounded_context' => $retrieval['context'],
                'prompt_preview' => $retrieval['prompt'],
            ],
            'quick_actions' => $this->buildQuickActions($intent, $route),
            'escalate_to_expert' => $needsExpert,
            'suggested_title' => $this->buildSuggestedTitle($crop, $category, $cleanMessage),
            'suggested_expert_focus' => $this->mapCategoryToSpecialty($category),
        ];
    }

    private function tokenize(string $message): array
    {
        $words = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($message), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_filter(array_unique($words), function (string $word): bool {
            return mb_strlen($word) > 2 && !in_array($word, $this->stopWords, true);
        }));
    }

    private function rankKnowledge(array $tokens, string $message): array
    {
        $message = mb_strtolower($message);
        $ranked = [];

        foreach ($this->knowledgeBase as $entry) {
            $title = mb_strtolower((string) ($entry['title'] ?? ''));
            $topic = mb_strtolower((string) ($entry['topic'] ?? ''));
            $content = mb_strtolower((string) ($entry['content'] ?? ''));
            $tags = $this->normalizeTags($entry['tags'] ?? []);
            $score = 0;

            foreach ($tokens as $token) {
                if (str_contains($title, $token)) {
                    $score += 5;
                }

                if (str_contains($topic, $token)) {
                    $score += 4;
                }

                if (in_array($token, $tags, true)) {
                    $score += 4;
                }

                if (str_contains($content, $token)) {
                    $score += 2;
                }
            }

            if ($score === 0) {
                foreach ($tags as $tag) {
                    if ($tag !== '' && str_contains($message, $tag)) {
                        $score += 3;
                    }
                }
            }

            if ($score > 0) {
                $entry['score'] = $score;
                $entry['recommendations'] = $this->normalizeRecommendations($entry['recommendations'] ?? []);
                $ranked[] = $entry;
            }
        }

        usort($ranked, fn(array $left, array $right): int => $right['score'] <=> $left['score']);

        return $ranked;
    }

    private function composeReply(
        array $topMatches,
        array $actions,
        string $route,
        string $category,
        string $crop,
        string $intent,
        string $message,
        string $confidenceLabel
    ): string {
        if ($route === 'ESCALATE') {
            return $this->composeEscalationReply();
        }

        if ($intent === 'HOW_TO_BEGINNER') {
            return $this->composeStarterGuideReply($message, $crop);
        }

        if ($intent === 'GENERAL_KNOWLEDGE') {
            $educationalReply = $this->composeEducationalReply($message, $crop, $category, $topMatches);
            if ($educationalReply !== '') {
                return $educationalReply;
            }
        }

        if ($intent === 'DIAGNOSIS') {
            return $this->composeDiagnosisReply($message, $crop, $category, $actions, $topMatches, $route, $confidenceLabel);
        }

        if ($topMatches === []) {
            return $this->composeGeneralPracticalReply($message, $crop);
        }

        $summary = "I know it is stressful when a field problem starts moving faster than expected. Based on your message, this looks like a {$category} concern";
        if ($crop !== 'General farming') {
            $summary .= " in {$crop}.";
        } else {
            $summary .= '.';
        }

        $guidance = $this->formatLines('Do these next', array_slice($actions, 0, 4));
        $closing = "Keep checking the crop morning and late afternoon, and adjust your next step based on whether the affected area is improving or spreading.";

        return trim($summary . "\n\n" . $guidance . "\n\n" . $closing);
    }

    private function composeEscalationReply(): string
    {
        return 'This is a complex concern. Let me escalate this to our human Agricultural Consultants who can assist you further.';
    }

    private function composeFallbackDiagnosisReply(string $message, string $crop): string
    {
        $normalized = mb_strtolower($message);
        $cropText = $crop !== 'General farming' ? mb_strtolower($crop) : 'the crop';

        if (str_contains($normalized, 'brown') || str_contains($normalized, 'spot') || str_contains($normalized, 'mold') || str_contains($normalized, 'fungus')) {
            return "Possible Causes:\n- Leaf spot, blight, or fungal disease.\n- Leaves staying wet too long.\n- Poor airflow between plants.\n\nWhat to Check:\n- Are spots growing larger or joining together?\n- Are lower leaves affected first?\n- Does the field stay wet in the morning?\n\nImmediate Actions:\n- Remove heavily infected leaves and avoid overhead watering.\n- Improve spacing or pruning for airflow.\n- Apply a labeled copper fungicide if spots continue spreading.\n- Rotate crops next season to reduce disease buildup.";
        }

        if (str_contains($normalized, 'yellow')) {
            return "Possible Causes:\n- Nitrogen deficiency.\n- Too much or too little water.\n- Root stress or early disease pressure in {$cropText}.\n\nWhat to Check:\n- Are older leaves yellow first?\n- Is the soil too wet or too dry?\n- Are there spots, insects, or weak roots?\n\nImmediate Actions:\n- Check soil moisture before watering again.\n- Apply a light nitrogen side-dress if older leaves are yellow.\n- Remove badly affected leaves and keep the field clean.\n- Monitor new growth for 3-5 days.";
        }

        if (str_contains($normalized, 'bug') || str_contains($normalized, 'worm') || str_contains($normalized, 'insect') || str_contains($normalized, 'eating') || str_contains($normalized, 'hole')) {
            return "Possible Causes:\n- Caterpillars, borers, beetles, aphids, or cutworms.\n- Weeds or crop debris sheltering pests.\n\nWhat to Check:\n- Look under leaves and inside curled leaves.\n- Check stems and the soil near the plant base early morning.\n- Note if damage is chewing, sap-sucking, or stem boring.\n\nImmediate Actions:\n- Handpick larger worms when practical.\n- Spray neem oil or insecticidal soap for soft-bodied pests.\n- Remove weeds and crop debris where pests hide.\n- Recheck the same area after 2-3 days.";
        }

        if (str_contains($normalized, 'wilt') || str_contains($normalized, 'droop')) {
            return "Possible Causes:\n- Water stress.\n- Damaged roots.\n- Blocked stems or soil-borne disease.\n\nWhat to Check:\n- Is the soil dry, soggy, or compacted?\n- Are roots brown, soft, or damaged?\n- Do plants recover at night or stay wilted?\n\nImmediate Actions:\n- Check soil moisture 5-10 cm deep before adding water.\n- Improve drainage if soil is soggy.\n- Remove plants that fully collapse to reduce spread.\n- Avoid injuring roots during weeding or cultivation.";
        }

        return "Possible Causes:\n- Pest feeding.\n- Nutrient stress.\n- Water stress.\n- Early disease pressure.\n\nWhat to Check:\n- Inspect leaves, stems, roots, and nearby soil closely.\n- Check if the issue starts in one area or across the field.\n- Look for insects, spots, rotting, or weak roots.\n\nImmediate Actions:\n- Remove badly affected plant parts.\n- Keep watering steady, not excessive.\n- Use neem oil for visible insect pests.\n- Use a labeled copper fungicide only when leaf spots or blight continue spreading.";
    }

    private function composeDiagnosisReply(
        string $message,
        string $crop,
        string $category,
        array $actions,
        array $topMatches,
        string $route,
        string $confidenceLabel
    ): string {
        if ($topMatches === []) {
            $reply = $this->composeFallbackDiagnosisReply($message, $crop);
        } else {
            $possibleCauses = array_map(
                fn(array $entry): string => $entry['title'] ?? $category,
                array_slice($topMatches, 0, 3)
            );

            $reply = "Possible Causes:\n"
                . $this->formatBulletLines($possibleCauses)
                . "\n\nWhat to Check:\n"
                . "- Look under leaves, around stems, and near the soil line.\n"
                . "- Check if the problem starts on old leaves or new leaves.\n"
                . "- Check soil moisture 5-10 cm deep before adding water.\n"
                . "- Note whether the affected area is spreading.\n\n"
                . "Immediate Actions:\n"
                . $this->formatBulletLines(array_slice($actions, 0, 4));
        }

        $reply .= "\n\nWhen to Escalate:\n"
            . "Ask a consultant if the problem spreads quickly, affects a large area, or does not improve after safe first steps.";

        if ($route === 'AI_PLUS_SUGGESTION') {
            $reply .= "\n\nA consultant can also review this if you can upload a clear photo.";
        }

        return $reply . "\n\nHow many days has this been happening?";
    }

    private function composeGeneralPracticalReply(string $message, string $crop): string
    {
        $normalized = mb_strtolower($message);
        $cropText = $crop !== 'General farming' ? mb_strtolower($crop) : 'your crop';

        if (str_contains($normalized, 'weather') || str_contains($normalized, 'rain') || str_contains($normalized, 'storm')) {
            return "Before heavy rain or storms, clear drainage canals, support weak plants, avoid applying fertilizer right before rainfall, and harvest mature produce early if possible.\n\nAfterward, check for waterlogging, broken stems, and disease spots because wet conditions can trigger fungal problems quickly.";
        }

        if (str_contains($normalized, 'fertilizer') || str_contains($normalized, 'nutrient')) {
            return "For fertilizer, match the application to the crop stage: nitrogen supports leafy growth, phosphorus helps roots, and potassium improves strength, flowering, and stress tolerance.\n\nUse compost when available, apply fertilizer in split doses, and avoid over-applying before heavy rain so nutrients are not wasted.";
        }

        if (str_contains($normalized, 'water') || str_contains($normalized, 'irrigation')) {
            return "Good irrigation means keeping moisture steady without leaving roots soaked. Water deeply enough to reach the root zone, then let the topsoil slightly dry before watering again.\n\nMorning watering is usually best because leaves dry faster and disease pressure stays lower.";
        }

        return "For {$cropText}, start with healthy planting material, good drainage, proper spacing, steady watering, and weekly field scouting.\n\nWatch leaves, stems, soil moisture, and pest activity closely; small problems are much easier to manage when caught early.";
    }

    private function composeStarterGuideReply(string $message, string $crop): string
    {
        $cropText = $crop !== 'General farming' ? mb_strtolower($crop) : 'your farm';
        $question = $crop === 'General farming'
            ? "\n\nWhat crop are you growing, and where is your farm located?"
            : "\n\nWhere is your farm located so I can make the advice more local?";

        return "Overview:\nManaging {$cropText} well means taking care of soil, water, crop timing, pests, weeds, and daily field observations. Good farm management is mostly about doing simple tasks on time.\n\n"
            . "Key Steps / Practices:\n"
            . "- Choose crops that fit your soil, season, and water supply.\n"
            . "- Prepare the land well before planting.\n"
            . "- Water regularly, but do not let roots stay soaked.\n"
            . "- Use compost or fertilizer at the right crop stage.\n"
            . "- Check the field weekly for pests, diseases, weeds, and weak plants.\n"
            . "- Keep records of planting dates, fertilizer, sprays, harvest, and problems.\n\n"
            . "Common Mistakes to Avoid:\n"
            . "- Planting at the wrong season.\n"
            . "- Using too much fertilizer or pesticide.\n"
            . "- Ignoring weeds while crops are still young.\n"
            . "- Waiting too long before acting on pests or disease signs.\n\n"
            . "Simple Tips for Beginners:\n"
            . "- Visit the field every day, even for a short check.\n"
            . "- Start small, learn from one crop cycle, then expand.\n"
            . "- Ask nearby farmers what grows well in your area.\n"
            . "- Take photos of problems so you can compare changes over time."
            . $question;
    }

    private function composeEducationalReply(string $message, string $crop, string $category, array $topMatches): string
    {
        $normalized = mb_strtolower($message);

        if (str_contains($normalized, 'pest')) {
            return match (mb_strtolower($crop)) {
                'corn', 'maize' => "Common pests in corn fields include:\n- Corn borer: damages stems and can reduce yield.\n- Armyworms: feed quickly on leaves, especially in groups.\n- Corn earworm: attacks the ears and kernels.\n- Aphids: suck plant sap and can weaken plants.\n- Cutworms: cut young seedlings near the soil line.\n\nWalk the field regularly, check leaf undersides and whorls, and act early when pest numbers are still low.",
                'rice' => "Common pests in rice include:\n- Stem borers: cause dead hearts or white heads.\n- Brown planthoppers: suck sap and can cause hopper burn.\n- Leaf folders: fold and scrape leaves.\n- Rice bugs: damage grains during milk stage.\n- Golden apple snails: cut young seedlings.\n\nCheck fields often, especially after rain or during warm humid weather, so you can respond before damage spreads.",
                default => "Common field pests include caterpillars, aphids, borers, beetles, mites, thrips, and cutworms. They may chew leaves, bore into stems, suck plant sap, or damage flowers and fruits.\n\nRegular scouting is the best first step: inspect young leaves, leaf undersides, stems, flowers, and soil near seedlings.",
            };
        }

        if (str_contains($normalized, 'disease')) {
            return "Common crop diseases include leaf spots, blights, wilts, rots, rusts, and mildews. Many are encouraged by humid weather, poor airflow, infected plant debris, or water sitting on leaves.\n\nUse clean seed, avoid overhead watering when possible, remove badly infected plant parts, rotate crops, and improve spacing for airflow.";
        }

        if (str_contains($normalized, 'soil')) {
            return "Healthy soil should hold moisture, drain well, support roots, and contain enough organic matter. Compost, crop residues, cover crops, and balanced fertilizer all help improve soil over time.\n\nA soil test is the best guide before adding lime or major fertilizer, because pH and nutrient needs vary by field.";
        }

        if (str_contains($normalized, 'plant') || str_contains($normalized, 'grow')) {
            $cropText = $crop !== 'General farming' ? mb_strtolower($crop) : 'the crop';
            return "For planting {$cropText}, start with healthy seed or seedlings, prepare loose well-drained soil, follow proper spacing, and water gently after planting.\n\nKeep weeds down early, monitor for pests weekly, and adjust fertilizer based on crop stage and field condition.";
        }

        if ($topMatches !== []) {
            $examples = array_map(
                fn(array $entry): string => $entry['title'] ?? ($entry['topic'] ?? 'Related farm topic'),
                array_slice($topMatches, 0, 3)
            );
            $definition = $this->summarizeKnowledgeEntry($topMatches[0]);

            return "Definition:\n{$definition}\n\n"
                . "Examples:\n"
                . $this->formatBulletLines($examples)
                . "\n\nWhy it matters:\n"
                . "Understanding this helps you choose the right action early, avoid wasted inputs, and protect crop yield.";
        }

        return "Yes, I can explain that. In general, good farming decisions start with the crop, field condition, weather, soil moisture, and pest or disease pressure.\n\nTell me the crop or topic you want to learn about, and I can give a simple field-ready explanation.";
    }

    private function buildActionList(array $topMatches): array
    {
        $actions = [];

        foreach ($topMatches as $entry) {
            foreach ($entry['recommendations'] ?? [] as $recommendation) {
                $cleanRecommendation = trim((string) $recommendation);
                if ($cleanRecommendation !== '' && !in_array($cleanRecommendation, $actions, true)) {
                    $actions[] = $cleanRecommendation;
                }
            }
        }

        if ($actions === []) {
            $actions = [
                'Observe the pattern of damage and document any change over the next 24 hours.',
                'Separate badly affected plants from healthy plants when possible.',
                'Create a consultation so an agricultural expert can verify the diagnosis.',
            ];
        }

        return array_slice($actions, 0, 5);
    }

    private function shouldEscalate(string $message, array $topMatches, array $context, string $intent): bool
    {
        $confidence = $this->scoreConfidence($message, $topMatches, $context, $intent);

        return $this->selectRoute($message, $intent, $confidence['label']) === 'ESCALATE';
    }

    private function scoreConfidence(string $message, array $topMatches, array $context, string $intent): array
    {
        if ($this->isCriticalHighRisk($message)) {
            return ['score' => 0, 'label' => 'LOW'];
        }

        $topScore = (int) ($topMatches[0]['score'] ?? 0);
        $score = match (true) {
            $topScore >= 14 => 78,
            $topScore >= 7 => 62,
            $topScore > 0 => 48,
            default => 38,
        };

        if (in_array($intent, ['GENERAL_KNOWLEDGE', 'HOW_TO_BEGINNER'], true)) {
            $score = max($score, 82);
        }

        if ($intent === 'DIAGNOSIS' && $topMatches === [] && $this->hasStandardSymptomSignal($message)) {
            $score = 55;
        }

        if (($context['crop'] ?? '') !== '' || $this->detectCrop($message, $topMatches) !== 'General farming') {
            $score += 8;
        }

        $score = max(0, min(100, $score));
        $label = $score >= 70 ? 'HIGH' : ($score >= 45 ? 'MEDIUM' : 'LOW');

        return ['score' => $score, 'label' => $label];
    }

    private function selectRoute(string $message, string $intent, string $confidenceLabel): string
    {
        if ($this->isCriticalHighRisk($message) || $confidenceLabel === 'LOW') {
            return 'ESCALATE';
        }

        if ($intent === 'DIAGNOSIS' && $confidenceLabel === 'MEDIUM') {
            return 'AI_PLUS_SUGGESTION';
        }

        return 'AI_ONLY';
    }

    private function isCriticalHighRisk(string $message): bool
    {
        $criticalWords = [
            'massive crop failure', 'catastrophic', 'entire farm', 'whole farm', 'entire field died',
            'whole field died', 'all crops died', 'all plants died', 'farm died overnight',
            'field died overnight', '50-hectare', '50 hectare', 'lost everything',
            'severe infestation', 'massive infestation', 'whole crop is dying', 'entire crop is dying',
            'restricted chemical', 'regulated chemical', 'industrial chemical', 'prescription pesticide',
            'restricted pesticide', 'paraquat', 'methyl bromide', 'aluminum phosphide',
        ];

        $normalized = mb_strtolower($message);

        foreach ($criticalWords as $word) {
            if (str_contains($normalized, $word)) {
                return true;
            }
        }

        return false;
    }

    private function hasStandardSymptomSignal(string $message): bool
    {
        $normalized = mb_strtolower($message);
        $signals = [
            'yellow', 'brown', 'spot', 'spots', 'wilt', 'wilting', 'droop', 'drooping',
            'bug', 'bugs', 'worm', 'worms', 'insect', 'hole', 'holes', 'mold', 'fungus',
            'curl', 'rotting', 'rot', 'eating',
        ];

        foreach ($signals as $signal) {
            if (str_contains($normalized, $signal)) {
                return true;
            }
        }

        return false;
    }

    private function buildQuickActions(string $intent, string $route): array
    {
        $actions = [
            ['label' => 'Pest Control', 'action' => 'topic:pest_control', 'prompt' => 'How do I control common pests safely?'],
            ['label' => 'Soil Health', 'action' => 'topic:soil_health', 'prompt' => 'How can I improve my soil health?'],
            ['label' => 'Fertilizer Guide', 'action' => 'topic:fertilizer', 'prompt' => 'How should I use fertilizer properly?'],
        ];

        if ($intent === 'DIAGNOSIS' || $route === 'ESCALATE') {
            $actions[] = ['label' => 'Talk to Expert', 'action' => 'consultation:create'];
        }

        return $actions;
    }

    private function summarizeKnowledgeEntry(array $entry): string
    {
        if (trim((string) ($entry['supporting_excerpt'] ?? '')) !== '') {
            return trim((string) $entry['supporting_excerpt']);
        }

        $content = trim((string) ($entry['content'] ?? ''));
        if ($content === '') {
            return (string) ($entry['title'] ?? 'This is an important farming topic.');
        }

        $content = (string) preg_replace('/\s+/', ' ', $content);
        return mb_strlen($content) > 220 ? mb_substr($content, 0, 217) . '...' : $content;
    }

    private function isBroadBeginnerQuestion(string $message): bool
    {
        $normalized = mb_strtolower($message);

        $broadTopics = [
            'manage my farm', 'manage a farm', 'farm well', 'farm better', 'good farmer',
            'start farming', 'beginner farmer', 'new farmer', 'basic farming', 'farm management',
            'manage crops', 'improve my farm', 'run my farm', 'take care of my farm',
        ];

        foreach ($broadTopics as $topic) {
            if (str_contains($normalized, $topic)) {
                return true;
            }
        }

        return (bool) preg_match('/\b(how|what)\b.*\b(manage|start|begin|improve|run|take care)\b.*\b(farm|farming|crops?)\b/u', $normalized);
    }

    private function detectIntent(string $message): string
    {
        $normalized = mb_strtolower($message);

        if ($this->isCriticalHighRisk($message)) {
            return 'CRITICAL_HIGH_RISK';
        }

        if ($this->isBroadBeginnerQuestion($message)) {
            return 'HOW_TO_BEGINNER';
        }

        $howToPatterns = [
            '/\bhow\s+(do|does|to|can|should)\b.*\b(plant|grow|manage|start|begin|improve|water|irrigate|fertiliz|prepare|care)\b/u',
            '/\b(best way|steps|guide)\b.*\b(plant|grow|farm|crop|soil|water|fertiliz)\b/u',
        ];

        $informationalPatterns = [
            '/\bwhat\s+(are|is)\b/u',
            '/\bwhy\s+(do|does|is|are)\b/u',
            '/\b(list|types|examples|common|definition|define|meaning|explain|teach|learn)\b/u',
        ];

        $diagnosisPatterns = [
            '/\b(my|our|field|plants?|leaves|stems?|roots?|fruits?)\b.*\b(yellow|brown|spots?|wilting|dying|rotting|holes?|curling|mold|worms?|bugs?|damage)\b/u',
            '/\b(i see|i found|there are|there is|symptoms?|problem|issue|attack|infestation)\b/u',
            '/\b(yellow leaves?|brown spots?|bugs?\s+(are\s+)?eating|worms?\s+(are\s+)?eating|holes? in leaves?|wilting|rotting|mold|leaf curl)\b/u',
        ];

        foreach ($diagnosisPatterns as $pattern) {
            if (preg_match($pattern, $normalized)) {
                return 'DIAGNOSIS';
            }
        }

        foreach ($howToPatterns as $pattern) {
            if (preg_match($pattern, $normalized)) {
                return 'HOW_TO_BEGINNER';
            }
        }

        foreach ($informationalPatterns as $pattern) {
            if (preg_match($pattern, $normalized)) {
                return 'GENERAL_KNOWLEDGE';
            }
        }

        return 'GENERAL_KNOWLEDGE';
    }

    private function detectCategory(string $message, array $topMatches): string
    {
        $message = mb_strtolower($message);

        $map = [
            'Pest and Disease' => ['pest', 'worm', 'blight', 'fungus', 'spot', 'mold', 'leaf curl', 'armyworm', 'disease'],
            'Soil Management' => ['soil', 'ph', 'acidity', 'compost', 'salinity'],
            'Crop Nutrition' => ['fertilizer', 'nutrient', 'nitrogen', 'yellowing', 'deficiency'],
            'Water and Irrigation' => ['water', 'flood', 'waterlogging', 'drainage', 'irrigation'],
            'Farming Practices' => ['spacing', 'mulch', 'pruning', 'rotation', 'transplant'],
        ];

        foreach ($map as $label => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($message, $keyword)) {
                    return $label;
                }
            }
        }

        if ($topMatches !== []) {
            return (string) ($topMatches[0]['topic'] ?? 'General Advisory');
        }

        return 'General Advisory';
    }

    private function detectCrop(string $message, array $topMatches): string
    {
        $message = mb_strtolower($message);
        $crops = ['rice', 'corn', 'maize', 'tomato', 'onion', 'banana', 'coconut', 'pechay', 'vegetable'];

        foreach ($crops as $crop) {
            if (str_contains($message, $crop)) {
                return ucfirst($crop);
            }
        }

        foreach ($topMatches as $entry) {
            foreach ($this->normalizeTags($entry['tags'] ?? []) as $tag) {
                if (in_array($tag, $crops, true)) {
                    return ucfirst($tag);
                }
            }
        }

        return 'General farming';
    }

    private function buildSuggestedTitle(string $crop, string $category, string $message): string
    {
        $prefix = $crop !== 'General farming' ? $crop : 'Farm';
        $snippet = trim((string) preg_replace('/\s+/', ' ', $message));
        $snippet = mb_substr($snippet, 0, 42);

        return "{$prefix} {$category}: {$snippet}";
    }

    private function mapCategoryToSpecialty(string $category): string
    {
        return match ($category) {
            'Pest and Disease' => 'Pest Management',
            'Soil Management' => 'Soil Health',
            'Crop Nutrition' => 'Crop Nutrition',
            'Water and Irrigation' => 'Irrigation & Farm Practices',
            default => 'General Agronomy',
        };
    }

    private function normalizeTags(array|string $tags): array
    {
        if (is_string($tags)) {
            $tags = preg_split('/\s*,\s*/', mb_strtolower($tags), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        }

        return array_values(array_filter(array_map(
            fn(string $tag): string => trim(mb_strtolower($tag)),
            $tags
        )));
    }

    private function normalizeRecommendations(array|string $recommendations): array
    {
        if (is_string($recommendations)) {
            $recommendations = preg_split('/\r\n|\r|\n|\|{2}/', $recommendations, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        }

        return array_values(array_filter(array_map(
            fn(string $line): string => trim($line),
            $recommendations
        )));
    }

    private function formatLines(string $title, array $items): string
    {
        if ($items === []) {
            return '';
        }

        return $title . ":\n" . $this->formatBulletLines($items);
    }

    private function formatBulletLines(array $items): string
    {
        $lines = array_map(fn(string $item): string => "- {$item}", $items);

        return implode("\n", $lines);
    }
}

<?php

namespace App\Mcp\Tools;

use Laravel\Mcp\Tools\Tool;

class VmsAskAiTool extends Tool
{
    protected string $name = 'ask_ai';
    protected string $description = 'Ask general questions to the AI assistant (for non-database questions)';

    protected array $parameters = [
        'question' => [
            'type' => 'string',
            'description' => 'The question to ask the AI',
            'required' => true,
        ],
        'language' => [
            'type' => 'string',
            'description' => 'Preferred language: auto, en, bn (default: auto)',
            'required' => false,
            'default' => 'auto',
        ],
    ];

    public function execute(array $parameters): array
    {
        $question = $parameters['question'];
        $language = $parameters['language'] ?? 'auto';

        $responses = [
            'en' => [
                'greeting' => "Hello! I'm the VMS Assistant. How can I help you with visitor management today?",
                'help' => "I can help you with:\n- Visitor information and registration\n- Visit scheduling and tracking\n- Dashboard statistics\n- Database queries\n- General questions about the VMS system",
                'fallback' => "That's an interesting question. For specific data, I can query the database. Would you like me to look up specific visitor or visit information?",
            ],
            'bn' => [
                'greeting' => "হ্যালো! আমি VMS সহকারী। আজ আমি কীভাবে আপনাকে ভিজিটর ম্যানেজমেন্টে সাহায্য করতে পারি?",
                'help' => "আমি আপনাকে সাহায্য করতে পারি:\n- ভিজিটর তথ্য এবং নিবন্ধন\n- ভিজিট শিডিউল এবং ট্র্যাকিং\n- ড্যাশবোর্ড পরিসংখ্যান\n- ডাটাবেস কোয়েরি\n- VMS সিস্টেম সম্পর্কে সাধারণ প্রশ্ন",
                'fallback' => "এটি একটি আকর্ষণীয় প্রশ্ন। নির্দিষ্ট ডেটার জন্য, আমি ডাটাবেস কোয়েরি করতে পারি। আপনি কি নির্দিষ্ট ভিজিটর বা ভিজিট তথ্য খুঁজে দেখতে চান?",
            ],
        ];

        $questionLower = strtolower($question);
        $response = '';

        if (preg_match('/^(hi|hello|হ্যালো|নমস্কার|আসসালামু|আস্সালামু)/i', $question)) {
            $response = $language === 'bn' ? $responses['bn']['greeting'] : $responses['en']['greeting'];
        } elseif (preg_match('/(help|সাহায্য|কি করতে পারো|what can you do)/i', $question)) {
            $response = $language === 'bn' ? $responses['bn']['help'] : $responses['en']['help'];
        } else {
            $response = $language === 'bn' ? $responses['bn']['fallback'] : $responses['en']['fallback'];
        }

        return $this->text($response);
    }
}

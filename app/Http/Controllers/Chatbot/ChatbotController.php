<?php

namespace App\Http\Controllers\Chatbot;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    //
    public function index()
    {
        return view('chatbot.ai-chatbot');
    }
}

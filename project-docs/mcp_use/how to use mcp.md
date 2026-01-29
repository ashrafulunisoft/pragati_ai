# Laravel MCP সম্পদিক - সহজ ব্যাখ্যা

## 📚 MCP কি?

**MCP (Model Context Protocol)** হলো AI অ্যাসিস্ট্যান্ট যে AI এর সাথে আপনার কোড/সিস্টেম কানেক্ট করার একটি স্ট্যান্ডার্ড উপায়।

সহজ কথা বললে মনে রাখবেন:
- MCP হলো AI এবং আপনার কোডের মধ্যে একটি ব্রিজ
- AI যাতে আপনার কোডের ফাংশন ব্যবহার করতে পারে

## 🔧 MCP এর তিনটি প্রধান অংশ

### 1️⃣ Tools (টুলস) - ফাংশন

**Tools** হলো AI ব্যবহার করতে পারা ফাংশন।

```php
// সহজ একটি Tool তৈরি করা
class GetVisitorTool
{
    // Tool এর নাম ও বর্ণনা
    public function getName(): string
    {
        return 'get_visitor';
    }
    
    public function getDescription(): string
    {
        return 'ভিজিটরের তথ্য আনুন';
    }
    
    // Tool এর কাজ (execute)
    public function execute(array $params): array
    {
        $visitorId = $params['id'];
        
        // ডেটাবেস থেকে ডেটা আনুন
        $visitor = \App\Models\Visitor::find($visitorId);
        
        return [
            'success' => true,
            'data' => $visitor->toArray()
        ];
    }
}
```

### 2️⃣ Resources (রিসোর্সেস) - ডেটা উৎস

**Resources** হলো AI যা পড়তে পারা ডেটা বা তথ্য।

```php
// সহজ একটি Resource তৈরি করা
class VisitorsListResource
{
    public function getName(): string
    {
        return 'visitors_list';
    }
    
    public function getData(): array
    {
        // সব ভিজিটর তালিকা আনুন
        return \App\Models\Visitor::all()->toArray();
    }
}
```

### 3️⃣ Prompts (প্রম্পটস) - AI কে নির্দেশনা

**Prompts** হলো AI কে দেওয়া নির্দেশনা যা AI কীভাবে উত্তর দেবে।

```php
// সহজ একটি Prompt তৈরি করা
class DatabaseQueryPrompt
{
    public function getName(): string
    {
        return 'database_query';
    }
    
    public function getPrompt(): string
    {
        return 'আপনি একটি ডেটাবেস কোয়েরি। 
        ভিজিটর এবং ভিজিট টেবিল আছে।
        ডেটাবেস থেকে ডেটা আনার জন্য SQL কোয়েরি লিখুন।';
    }
}
```

## 🚀 কিভাবে Laravel এ MCP ব্যবহার করবেন?

### ধাপ ১: Service তৈরি করুন

```php
// app/Services/MCPService.php
namespace App\Services;

class MCPService
{
    private $tools = [];
    
    public function __construct()
    {
        // সব টুলস রেজিস্টার করুন
        $this->registerTool(new GetVisitorTool());
        $this->registerTool(new CreateVisitorTool());
    }
    
    private function registerTool($tool)
    {
        $this->tools[$tool->getName()] = $tool;
    }
    
    // AI কে টুলসের তালিকা দিন
    public function getAvailableTools(): array
    {
        return $this->tools;
    }
    
    // AI এর মেসেজ প্রসেস করুন
    public function processMessage(string $message): array
    {
        // এখান AI কল করে কোন টুল ব্যবহার করতে হবে
        $intent = $this->detectIntent($message);
        $tool = $this->tools[$intent] ?? null;
        
        if ($tool) {
            // টুল রান করুন
            return $tool->execute([
                'query' => $message
            ]);
        }
        
        return ['error' => 'দুঃখিত, আমি বুঝতে পারিনি'];
    }
}
```

### ধাপ ২: Service রেজিস্টার করুন

```php
// app/Providers/AppServiceProvider.php
public function register(): void
{
    // MCP service রেজিস্টার করুন
    $this->app->singleton('mcp', function ($app) {
        return new \App\Services\MCPService();
    });
}
```

### ধাপ ৩: ব্যবহার করুন

```php
// Tinker এ বা কোডে ব্যবহার করুন
$mcp = app('mcp');

// AI কে মেসেজ দিন
$result = $mcp->processMessage('ভিজিটর #5 এর তথ্য দিন');

// ফলাফ দেখুন
print_r($result);
```

## 🎯 সম্পূর্ণ উদাহরণ

```php
// User: "আজকে কত ভিজিটর এসেছে?"
// 
// MCP Flow:
// 1. User মেসেজ → MCP Service
// 2. MCP → Intent Detection (কী চাই)
// 3. MCP → Tool Selection (count_visitors_tool)
// 4. Tool → Execute (DB Query)
// 5. Tool → Return Result
// 6. MCP → Send to User
// 
// Result: "আজকে ৫ জন ভিজিটর এসেছে"
```

## 📝 মূল কথা মনে রাখবেন:

| অংশ | কাজ |
|-----|-----|
| **Tools** | AI কাজ করার জন্য ফাংশন |
| **Resources** | AI দেখার জন্য ডেটা |
| **Prompts** | AI কে নির্দেশনা |

এটিই MCP! 😊
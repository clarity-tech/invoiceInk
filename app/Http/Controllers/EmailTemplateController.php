<?php

namespace App\Http\Controllers;

use App\Enums\EmailTemplateType;
use App\Mail\Templates\DefaultEmailTemplates;
use App\Mail\Templates\EmailTemplateService;
use App\Mail\Templates\TemplateVariableResolver;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmailTemplateController extends Controller
{
    public function __construct(private EmailTemplateService $service) {}

    public function index(): Response
    {
        $organization = auth()->user()->currentTeam;
        $templates = $this->service->listForOrganization($organization);

        return Inertia::render('EmailTemplates/Index', [
            'templates' => $templates,
            'variables' => DefaultEmailTemplates::availableVariables(),
        ]);
    }

    public function edit(string $type): Response
    {
        $templateType = EmailTemplateType::from($type);
        $organization = auth()->user()->currentTeam;
        $resolved = $this->service->resolve($organization, $templateType);
        $default = DefaultEmailTemplates::get($templateType);

        return Inertia::render('EmailTemplates/Edit', [
            'templateType' => $templateType->value,
            'label' => $templateType->label(),
            'description' => $templateType->description(),
            'documentType' => $templateType->documentType(),
            'template' => $resolved,
            'defaultTemplate' => $default,
            'variables' => DefaultEmailTemplates::availableVariables(),
        ]);
    }

    public function update(Request $request, string $type): RedirectResponse
    {
        $templateType = EmailTemplateType::from($type);
        $organization = auth()->user()->currentTeam;

        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:500'],
            'body' => ['required', 'string', 'max:50000'],
        ]);

        $this->service->save($organization, $templateType, $validated['subject'], $validated['body']);

        return back()->with('success', 'Email template saved.');
    }

    public function destroy(string $type): RedirectResponse
    {
        $templateType = EmailTemplateType::from($type);
        $organization = auth()->user()->currentTeam;

        $this->service->resetToDefault($organization, $templateType);

        return back()->with('success', 'Email template reset to default.');
    }

    /**
     * Resolve a rendered template for the email modal (JSON API).
     */
    public function resolve(Request $request): JsonResponse
    {
        $request->validate([
            'template_type' => ['required', 'string'],
            'invoice_id' => ['required', 'integer', 'exists:invoices,id'],
        ]);

        $templateType = EmailTemplateType::from($request->template_type);
        $invoice = Invoice::findOrFail($request->invoice_id);

        $this->authorize('view', $invoice);

        $rendered = $this->service->render($invoice, $templateType);

        return response()->json($rendered);
    }

    /**
     * Preview a template with sample variable substitution (JSON API).
     */
    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'subject' => ['required', 'string'],
            'body' => ['required', 'string'],
            'invoice_id' => ['nullable', 'integer', 'exists:invoices,id'],
        ]);

        if ($request->invoice_id) {
            $invoice = Invoice::findOrFail($request->invoice_id);
            $this->authorize('view', $invoice);
            $variables = TemplateVariableResolver::resolve($invoice);
        } else {
            $variables = self::sampleVariables();
        }

        return response()->json([
            'subject' => TemplateVariableResolver::render($request->subject, $variables),
            'body' => TemplateVariableResolver::render($request->body, $variables),
        ]);
    }

    /**
     * @return array<string, string>
     */
    private static function sampleVariables(): array
    {
        return [
            '{{customer_name}}' => 'Acme Industries',
            '{{invoice_number}}' => 'INV-2026-0042',
            '{{amount_due}}' => '₹1,50,000.00',
            '{{subtotal}}' => '₹1,27,118.64',
            '{{tax_amount}}' => '₹22,881.36',
            '{{currency}}' => 'INR',
            '{{due_date}}' => 'Apr 15, 2026',
            '{{issue_date}}' => 'Mar 26, 2026',
            '{{organization_name}}' => 'Clarity Technologies Pvt Ltd',
            '{{organization_email}}' => 'accounts@claritytech.io',
            '{{view_url}}' => '#preview',
            '{{days_overdue}}' => '7',
            '{{status}}' => 'Sent',
        ];
    }
}

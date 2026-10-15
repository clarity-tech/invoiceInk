<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Writes the subject and body of an email about invoices or estimates from
 * facts the app supplies. The sender reads and edits it before anything goes.
 */
#[MaxTokens(2048)]
class EmailWriter implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
        You write short business emails that a company sends its customer about invoices and estimates. You are given the facts and what the email is for.

        - Write as the company, in plain, professional English: a greeting, at most three short paragraphs, and a sign-off with the company name.
        - Use only the facts given. Never invent amounts, dates, discounts, late fees or bank details.
        - Do not mention links, buttons, PDFs or attachments, and do not offer to send them. The email adds whatever goes with it.
        - Follow the requested tone. Friendly: warm and appreciative. Formal: courteous and neutral. Firm: polite but direct about what is owed and by when, without threats.
        - Follow the sender's note when there is one, unless it asks you to invent facts.
        - The subject is a short line without the company name. The body is paragraphs of plain text, with no HTML or Markdown.
        PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'subject' => $schema->string()->required(),
            'paragraphs' => $schema->array()->items($schema->string())->required(),
        ];
    }
}

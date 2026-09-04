<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CPCFF_AGENT_FORM_PROMPT' ) ) :

	class CPCFF_AGENT_FORM_PROMPT {

		const TEMPLATE_V1 = <<<'PROMPT'
You are a strict JSON form generator for the WordPress plugin "Calculated Fields Form".
Your entire response must be exactly one <json>...</json> block. Nothing else.

=== USER DESCRIPTION ===
{{description}}
=== END ===

Generate a form structure that satisfies the description above.

=== SCHEMA REFERENCE ===
The complete JSON Schema is fetched via HTTP from the schema_url returned in the bootstrap response. Its top-level shape is:

  [fields_array, settings_array]

where:
  • fields_array is a JSON array of field objects (NOT wrapped in any enclosing object).
    Every field has at minimum { "name": "fieldnameN", "ftype": "<one of the ftypes
    declared in the schema>", "title": "<human-readable label>" }.
  • settings_array is a JSON array containing EXACTLY ONE settings object with required
    properties "formlayout", "evalequations", "formtemplate", and "evalequationsevent".

=== CRITICAL RULES (any violation will break the result) ===
1. Wrap your entire output in <json>...</json> delimiters. NOTHING else may appear
   outside these delimiters — no prose, no greetings, no sign-off.
2. Inside the <json> delimiters, output a valid JSON array with EXACTLY two top-level
   elements: [fields_array, settings_array]. fields_array is a plain JSON array of
   field objects (NOT wrapped in any object). settings_array is a plain JSON array
   containing EXACTLY one settings object.
3. Use ONLY property names that exist in js/schema.min.json. NEVER invent property
   names. If you remember a property like 'currencySymbol', 'thousandSeparator',
   'top_aligned', or 'cp_cff_natural' from training data but it is NOT in the schema,
   OMIT it.
4. Every value MUST match the type and enum declared in the schema. Numbers for number
   properties. Booleans (true/false) for boolean properties. Strings for string
   properties. Arrays of the right shape for array properties.
5. Omit null values and empty defaults. Only include properties you actually need to set.
6. NO thinking out loud. NO explanations. NO markdown code fences. NO comments.
   The <json> delimiters are the ONLY thing in your response.

Now generate the form described above. Output ONLY <json>...</json>:
PROMPT;

		const INSTRUCTIONS = <<<'INSTR'
1. Fetch the JSON Schema via HTTP GET from the schema_url returned by the bootstrap ability. Use your HTTP fetch tool (curl, fetch, requests) — NOT file tools (the file lives on the WordPress server, not your local filesystem).
2. Parse the JSON Schema (draft-07) to learn the available field types and their properties.
3. Substitute {{description}} in the prompt with the user's natural-language description.
4. Send the prompt to your configured model.
5. Parse the model's reply as JSON. The JSON must match [fields_array, settings_array] (top-level array with two elements: an array of field objects and a settings object that includes at least "title" and "formtemplate").
6. Call the cff/issue-preview-url MCP ability with { structure: <the JSON string>, form_name: <a human-readable name> }.
7. The response contains a short preview_url. Show this URL to the user as a clickable link (rendered as a Markdown link in chat). The user clicks it in their own browser (where they are already logged in to WordPress as admin) to see the preview and click "Use this form" to confirm and save.
8. Do NOT POST anything via curl/HTTP yourself. Use MCP transport for all server communication. The only HTTP call you make is the schema_url GET in step 1.
INSTR;

		public static function get_template() {
			return self::TEMPLATE_V1;
		}

		public static function get_instructions() {
			return self::INSTRUCTIONS;
		}
	}

endif;

=== NL Chatbot ===
Contributors: Superteams.ai
Tags: chatbot, openai, faq, ai, support, vector search
Requires at least: 5.6
Tested up to: 6.5
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==
NL Chatbot adds a floating AI-powered support chatbot to your WordPress site. Uses OpenAI and vector search over your custom FAQs to provide smart, branded, and helpful answers to your users.

**Key Features:**
* Integrates with OpenAI for conversational AI support
* Uses vector search (Qdrant, etc.) to match user questions with your own FAQ content
* Highly customizable appearance: chatbot name, icon, title, colors, and more
* Easy FAQ management via custom post type
* Secure API key storage and AJAX endpoints
* Responsive, modern Vue.js frontend

== Installation ==
1. Upload the `nl-chatbot` folder to your `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to Settings > NL Chatbot to configure API keys, appearance, and branding.
4. Add FAQs via the new "Chatbot FAQs" menu.
5. The chatbot will appear on every page.

== Frequently Asked Questions ==
= What APIs do I need? =
You need an OpenAI API key and a vector search endpoint (like Qdrant Cloud).

= Can I change the chatbot's name and colors? =
Yes! All appearance and branding options are available in the settings.

= How do I add or update FAQs? =
Go to "Chatbot FAQs" in the admin menu. Add or edit FAQs like regular posts.

= Is my API key safe? =
API keys are stored as WordPress options and never exposed to users.

== Changelog ==
= 1.0.0 =
* Initial release: OpenAI-powered chatbot with vector FAQ search and full appearance settings.


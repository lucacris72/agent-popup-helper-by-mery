=== Agent PopUp Helper by Mery ===
Contributors: MarianoAkaMery
Tags: openai, chatbot, ai, popup, shortcode, wordpress
Requires at least: 6.0
Requires PHP: 8.0
Stable tag: 1.2.9
License: MIT
License URI: https://opensource.org/licenses/MIT

Agent PopUp Helper by Mery is a lightweight WordPress plugin that embeds your OpenAI ChatKit agent with shortcode and floating popup display modes.

== Description ==

Agent PopUp Helper by Mery helps WordPress site owners connect an OpenAI-hosted ChatKit agent to a clean frontend chatbot experience.

Main features:

* OpenAI API key stored server-side only
* OpenAI Workflow ID support
* Optional workflow version override
* Floating popup mode
* Shortcode mode with `[ml_chatbot]`
* Popup open delay and device visibility controls
* Brand name, logo, color, and title settings
* Modern responsive UI
* No heavy frontend framework or build tooling
* Simple WordPress filters for developer customization

This plugin is intentionally small and focused. It does not add analytics, chat history, embeddings, file upload, or enterprise features.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate the plugin through the `Plugins` screen in WordPress.
3. Open `Settings > Agent PopUp Helper by Mery`.
4. Save your OpenAI API key and Workflow ID.
5. Choose either `Shortcode window` or `Floating popup`.
6. If using shortcode mode, place `[ml_chatbot]` inside the page or post where you want the chatbot to appear.

== Frequently Asked Questions ==

= Do I need both an OpenAI API key and a Workflow ID? =

Yes. WordPress uses the API key on the server to create a ChatKit session, and the Workflow ID tells OpenAI which hosted agent should be used.

= Does this expose my OpenAI API key to visitors? =

No. The plugin only sends a short-lived ChatKit client secret to the browser. The OpenAI API key remains server-side.

= What is the difference between popup mode and shortcode mode? =

Popup mode injects a floating launcher automatically in the site footer. Shortcode mode renders the chatbot only where you place `[ml_chatbot]`.

= Can I customize the brand? =

Yes. You can set a brand name, logo, color, and chatbot title from the admin settings page.

== Changelog ==

= 1.2.9 =

* Production-ready cleanup after ChatKit integration debugging
* Improved ChatKit container rendering
* Better admin guidance and configuration status
* Added developer filters and release-ready metadata
* Added popup delay, visibility, and launcher text controls

== Upgrade Notice ==

= 1.2.9 =

This version improves popup controls and keeps the plugin positioning focused on OpenAI ChatKit agents for WordPress.

# Agent PopUp Helper by Mery

![Agent PopUp Helper by Mery cover](docs/images/agent-popup-helper-cover.png)

WordPress plugin for embedding your OpenAI ChatKit agent in two display modes:

- embedded chatbot via shortcode
- floating popup chatbot injected in the site footer

The plugin is designed as a small, editable, real-world v1 with clean PHP, vanilla JS, and custom CSS.

The current codebase keeps the existing public keys for compatibility:

- shortcode: `[ml_chatbot]`
- option key: `ml_chatbot_settings`

## Features

- WordPress admin settings page
- OpenAI API key stored server-side
- ChatKit integration for OpenAI-hosted agents
- Configurable OpenAI Workflow ID
- Optional workflow version override
- Display mode:
  - `Shortcode window`
  - `Floating popup`
- Popup position:
  - bottom right
  - bottom left
- Popup open delay
- Popup visibility:
  - desktop and mobile
  - desktop only
  - mobile only
- Optional launcher text toggle
- ChatKit UI controls:
  - chat history toggle
  - feedback buttons toggle
- Brand customization:
  - chatbot title
  - optional brand name
  - optional logo
  - theme color picker
  - popup launcher hover color picker
- AJAX backend with nonce validation
- No API key exposure in the frontend
- Small set of WordPress filters for developers

## Current Structure

```text
.
|-- .gitignore
|-- assets/
|   |-- css/
|   |   `-- ml-chatbot.css
|   `-- js/
|       |-- ml-chatbot-admin.js
|       `-- ml-chatbot.js
|-- includes/
|   |-- class-ml-chatbot-admin.php
|   |-- class-ml-chatbot-api.php
|   |-- class-ml-chatbot-plugin.php
|   `-- class-ml-chatbot-shortcode.php
|-- ml-chatbot-ai.php
|-- README.md
`-- uninstall.php
```

## Requirements

- WordPress 6+
- PHP 8+
- a valid OpenAI API key

## Installation

1. Copy the plugin folder into `wp-content/plugins/`
2. Activate `Agent PopUp Helper by Mery` from the WordPress admin
3. Open `Settings > Agent PopUp Helper by Mery`
4. Save:
   - OpenAI API key
   - workflow ID
   - workflow version if needed
   - display mode
   - branding options

## Usage

### Shortcode Mode

Set `Display mode` to `Shortcode window`, then place:

```text
[ml_chatbot]
```

inside a page or post where you want the chatbot to appear.

### Popup Mode

Set `Display mode` to `Floating popup`.

In this mode the plugin injects the chatbot automatically in the site footer. You do not need to place the shortcode for the popup itself.

## Branding Options

From the admin panel you can customize:

- `Brand name`
- `Brand logo`
- `Theme color`
- `Hover color`
- `Chatbot title`

The selected theme color is used to derive the chatbot accent styling automatically.
You can override the popup launcher hover color with a dedicated setting, or leave the automatic darker shade.

## Notes and Disclaimers

- If you use `Floating popup` mode, do not rely on `[ml_chatbot]` for the popup UI.
- Theme color, logo, and brand name affect only the chatbot UI, not the WordPress theme.
- Very light colors may reduce contrast and readability.
- ChatKit history and feedback buttons can be enabled or disabled from the plugin settings.
- Hiding model reasoning is not currently exposed as a documented public ChatKit option in this hosted integration.

## How ChatKit Works

- the frontend loads OpenAI ChatKit
- WordPress creates a short-lived ChatKit session server-side
- the plugin sends `POST /v1/chatkit/sessions`
- the request includes:
  - `user`
  - `workflow.id`
  - `workflow.version` if configured
- WordPress returns only the `client_secret` to the browser
- the browser never receives your OpenAI API key

## Security

- direct file access blocked with `ABSPATH`
- admin settings sanitized
- AJAX request protected with nonce
- OpenAI API key never exposed to visitors
- server-side OpenAI request via WordPress HTTP API

## Developer Hooks

This plugin stays intentionally small, but it exposes a few filters for developers who want to customize behaviour without editing core files:

- `aph_chatkit_user_identifier`
- `aph_chatkit_session_payload`
- `aph_chatkit_request_args`
- `aph_chatkit_timeout`
- `aph_frontend_config`
- `aph_chatbot_title`

## Development Notes

This plugin intentionally avoids:

- build tools
- frontend frameworks
- heavy architecture
- database chat history
- embeddings
- analytics
- enterprise features

The goal is a small plugin that is easy to read and maintain in VS Code.

The repository includes a minimal `.gitignore` for editor files, archives, logs, and common cache artifacts.

## GitHub Suggestions

Good repository description:

```text
WordPress plugin to embed your OpenAI ChatKit agent as a floating popup or embedded chatbot.
```

Suggested topics:

```text
wordpress plugin openai chatkit chatbot ai popup shortcode agent
```

## Maintainer

- GitHub: `MarianoAkaMery`
- LinkedIn: https://www.linkedin.com/in/salvatore-mariano-librici-0aaab3202/

## License

MIT

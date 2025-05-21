# nextneural-wp - AI-powered plugins for WordPress

[![GPLv2 License](https://img.shields.io/badge/license-GPLv2-blue.svg)](LICENSE)
[![WordPress Tested](https://img.shields.io/badge/wordpress-6.5+-blue.svg)](https://wordpress.org/plugins/)

This repository includes two plugins:
- **NL Chatbot**: Floating AI-powered natural language chatbot for WordPress, using OpenAI and vector FAQ search (Qdrant).
- **NL-Search**: AI-powered semantic search for WordPress posts and pages.

## Why did we build this?

Though there are many plugins for chatbots and search, we found that they either oversimplify or overcomplicate the process. We wanted to offer WordPress users lightweight options that just work. 

The first version is powered by [OpenAI](https://platform.openai.com) and [Qdrant Cloud](https://cloud.qdrant.io/signup). Upcoming versions will support other LLMs and vector databases.

---

## Table of Contents
- [NL Chatbot](#nl-chatbot-ai-powered-natural-language-chatbot-for-wordpress)
  - [Features](#features)
  - [Installation](#installation)
  - [FAQ](#faq)
  - [License](#license)
- [NL-Search](#nl-search-natural-language-search-for-wordpress)
  - [Features](#features-1)
  - [Installation](#installation-1)
  - [Configuration](#configuration)
  - [Usage](#usage)
  - [Contributing](#contributing)
  - [License](#license-1)

---

# NL Chatbot (AI-Powered Natural Language Chatbot for WordPress)

Floating lightweight AI-powered support chatbot for WordPress. Uses OpenAI and vector search over your own FAQs to provide smart, branded, and helpful answers to your users.

## Features
- 🤖 Conversational AI support using OpenAI
- 🔎 Vector search (using Qdrant) to match user questions with your FAQ content
- 🎨 Highly customizable appearance: chatbot name, icon, title, colors, and more
- 🗂️ Easy FAQ management (custom post type)
- 🔐 Secure API key storage and AJAX endpoints
- 📱 Responsive, modern Vue.js frontend

## Installation
1. Upload the `nl-chatbot` folder to your `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **Settings > NL Chatbot** to configure API keys, appearance, and branding.
4. Add FAQs via the new **Chatbot FAQs** menu.
5. The chatbot will appear on every page.

## FAQ
**What APIs do I need?**  
You need an OpenAI API key and a vector search endpoint (like Qdrant Cloud).

**Can I change the chatbot's name and colors?**  
Yes! All appearance and branding options are available in the settings.

**How do I add or update FAQs?**  
Go to "Chatbot FAQs" in the admin menu. Add or edit FAQs like regular posts.

**Is my API key safe?**  
API keys are stored as WordPress options and never exposed to users.

## License
GPLv2 or later. See [LICENSE](LICENSE).

---

# NL-Search (Natural Language Search for WordPress)

NL-Search replaces the default WordPress search with a powerful AI-driven semantic search using LLMs and vector search. It leverages OpenAI for embeddings and Qdrant as the vector database, enabling users to search your site using natural language queries.

**Features:**
- 🔍 Semantic search using OpenAI's LLM models
- 🗂️ Indexes posts and pages (configurable)
- 📝 Optionally includes post/page excerpt/description in the index
- ⚡ Batch reindexing with one click
- 🔄 Automatic sync: removes deleted posts/pages from Qdrant
- 🛠️ Admin UI for API keys, Qdrant endpoint, and indexing options
- 🧩 Seamless integration with WordPress search
- 🏷️ Fallback to keyword search (optional)

**Installation & Usage:**
- See the [`nl-search/readme.txt`](./nl-search/readme.txt) for WordPress-specific instructions.
- Activate from the WordPress admin, configure your API keys and options, and reindex your content.

---

## Roadmap
- **nl-chat**: AI-powered chat and Q&A for your WordPress site (coming soon)
- More plugins and features to supercharge your WordPress with AI

---

## Contributing

Pull requests, issues, and suggestions are welcome! Please open an issue or PR on [GitHub](https://github.com/superteams-ai/nextneural-wp).

---

## License

All plugins in this repository are licensed under the [GPLv2 or later](./nl-search/LICENSE.txt).

---

## 🚀 Supercharge your team with [Superteams.ai](https://www.superteams.ai)

*Superteams.ai builds next-generation AI tools for productivity, search, and knowledge management. Learn more and get in touch at [superteams.ai](https://www.superteams.ai)!* 
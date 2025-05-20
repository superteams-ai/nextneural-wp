# nextneural-wp

A collection of AI-powered plugins for WordPress by [Superteams.ai](https://www.superteams.ai).

This repository is a monorepo for advanced AI features in WordPress. Each plugin lives in its own subdirectory. The first plugin is **NL-Search** (Natural Language Search). More plugins are coming soon!

---

## Plugins

### `nl-search` – Natural Language Search for WordPress

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
# NL-Search (Natural Language Search for WordPress)

NL-Search replaces the default WordPress search with a powerful AI-driven semantic search using LLMs and vector search. It leverages OpenAI for embeddings and Qdrant as the vector database, enabling users to search your site using natural language queries.

---

## Features

- 🔍 **Semantic search** using OpenAI's LLM models
- 🗂️ **Indexes posts and pages** (configurable)
- 📝 **Optionally includes post/page excerpt/description** in the index
- ⚡ **Batch reindexing** with one click
- 🔄 **Automatic sync**: removes deleted posts/pages from Qdrant
- 🛠️ **Admin UI** for API keys, Qdrant endpoint, and indexing options
- 🧩 **Seamless integration** with WordPress search
- 🏷️ **Fallback to keyword search** (optional)
- 🚀 Developed by [Superteams.ai](https://www.superteams.ai)

---

## Installation

1. Clone or download this repository into your WordPress `wp-content/plugins/` directory:
   ```sh
   git clone https://github.com/your-org/nl-search.git
   ```
2. Activate the plugin from the WordPress admin (`Plugins > Installed Plugins`).
3. Go to `Settings > NL Search` to configure:
   - OpenAI API key
   - Qdrant endpoint and API key
   - Indexing options (posts, pages, include excerpt)
4. Click **Reindex Posts** to index your site's content for semantic search.

---

## Configuration

- **OpenAI API Key:** Get yours from [OpenAI dashboard](https://platform.openai.com/account/api-keys).
- **Qdrant Endpoint:** Use your Qdrant Cloud or self-hosted instance (e.g., `https://your-instance.cloud.qdrant.io`).
- **Qdrant API Key:** Required if your Qdrant instance is not public.
- **Indexing Options:**
  - Index Posts
  - Index Pages
  - Include Excerpt/Description

---

## Usage

- Use the default WordPress search box—results will be powered by semantic search.
- Manage and reindex content from `Settings > NL Search`.
- Deleted posts/pages are automatically removed from Qdrant on reindex.

---

## Contributing

Pull requests, issues, and suggestions are welcome! Please open an issue or PR on [GitHub](https://github.com/your-org/nl-search).

---

## License

This plugin is licensed under the [GPLv2 or later](./LICENSE.txt).

---

## 🚀 Supercharge your workflow with [Superteams.ai](https://www.superteams.ai)

*Superteams.ai builds next-generation AI tools for productivity, search, and knowledge management. Learn more and get in touch at [superteams.ai](https://www.superteams.ai)!* 

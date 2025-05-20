=== Natural Language Search (AI) ===
Contributors: superteamsai
Tags: search, ai, openai, qdrant, semantic, vector, llm, natural language
Requires at least: 5.0
Tested up to: 6.5
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==
NL-Search (Natural Language Search) replaces the default WordPress search with a powerful AI-driven semantic search using LLMs and vector search. It leverages OpenAI for embeddings and Qdrant as the vector database, enabling users to search your site using natural language queries.

* Uses OpenAI's LLM models for embedding generation
* Stores and searches embeddings in Qdrant (vector database)
* Integrates with WordPress search
* Admin UI for API keys and Qdrant endpoint
* One-click reindexing of all posts
* Automatic sync: removes deleted posts from Qdrant

== Installation ==
1. Upload the plugin files to the `/wp-content/plugins/nl-search` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to Settings > NL Search to configure your OpenAI API key and Qdrant endpoint.
4. Click 'Reindex Posts' to index your site's content for semantic search.

== Frequently Asked Questions ==
= What do I need to use this plugin? =
You need an OpenAI API key and a Qdrant instance (cloud or self-hosted). Enter these in the plugin settings.

= What data is sent to OpenAI and Qdrant? =
Only post titles and content are sent to OpenAI for embedding. Embeddings and post titles are stored in Qdrant.

= Does this replace the default WordPress search? =
Yes, it overrides the default search with semantic search. If semantic search fails, you can enable fallback to keyword search.

= Can I use other LLMs or vector databases? =
Currently, only OpenAI and Qdrant are supported. Support for more LLMs and vector DBs is coming soon.

== Changelog ==
= 1.0.0 =
* Initial release: Semantic search with OpenAI and Qdrant, admin UI, batch reindex, and auto-sync.

== Upgrade Notice ==
= 1.0.0 =
First release. Please reindex your posts after upgrading. 
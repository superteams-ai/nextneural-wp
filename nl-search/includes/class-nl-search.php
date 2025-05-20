<?php

class NL_Search {

    public function run() {
        add_filter('posts_search', [$this, 'override_search'], 10, 2);
    }

    /**
     * Intercepts the WordPress search query and replaces it with semantic vector search.
     */
    public function override_search($search, $wp_query) {
        if (!is_admin() && $wp_query->is_search()) {
            $user_query = $wp_query->get('s');

            // Get semantic match post IDs
            $post_ids = $this->semantic_search($user_query);

            if (!empty($post_ids)) {
                global $wpdb;
                $ids = implode(',', array_map('intval', $post_ids));
                return " AND {$wpdb->posts}.ID IN ($ids) ";
            }
        }

        return $search;
    }

    /**
     * Converts query to embedding and retrieves vector matches.
     */
    private function semantic_search($query) {
        $embedding = $this->get_openai_embedding($query);

        if (empty($embedding)) {
            return []; // fallback: return nothing
        }

        return $this->search_qdrant($embedding);
    }

    /**
     * Calls OpenAI API to get embeddings for a given query string.
     */
    private function get_openai_embedding($text) {
        $api_key = trim(get_option('nl_search_openai_api_key'));
        if (!$api_key || empty($text)) {
            return [];
        }

        $response = wp_remote_post('https://api.openai.com/v1/embeddings', [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json'
            ],
            'body'    => json_encode([
                'input' => $text,
                'model' => 'text-embedding-3-small'
            ]),
            'timeout' => 15,
        ]);

        if (is_wp_error($response)) {
            error_log('OpenAI API error: ' . $response->get_error_message());
            return [];
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        return $body['data'][0]['embedding'] ?? [];
    }

    /**
     * Sends embedding to Qdrant and returns matched post IDs.
     */
    private function search_qdrant($embedding) {
        $qdrant_url     = rtrim(get_option('nl_search_qdrant_url'), '/');
        $qdrant_api_key = trim(get_option('nl_search_qdrant_api_key'));
        $collection     = nl_search_get_collection_name();

        if (!$qdrant_url || empty($embedding)) {
            return [];
        }

        $headers = ['Content-Type' => 'application/json'];
        if (!empty($qdrant_api_key)) {
            $headers['api-key'] = $qdrant_api_key;
        }

        $response = wp_remote_post("$qdrant_url/collections/$collection/points/query", [
            'headers' => $headers,
            'body'    => json_encode([
                'query' => $embedding,
                'limit'    => 10,
                'with_payload' => true
            ]),
            'timeout' => 10,
        ]);

        if (is_wp_error($response)) {
            error_log('Qdrant API error: ' . $response->get_error_message());
            return [];
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (!isset($body['result'])) {
            error_log('Qdrant response missing result');
            return [];
        }

        $ids = array_map(function($item) {
            if (isset($item['id'])) {
                return $item['id'];
            } elseif (isset($item['id']['num'])) {
                return $item['id']['num'];
            } elseif (isset($item['id']['uuid'])) {
                return $item['id']['uuid'];
            }
            return null;
        }, $body['result']);
        return array_values(array_filter($ids, fn($id) => $id !== null));
    }
}
<?php
/**
 * Chunker - Splits content into manageable chunks for context retrieval.
 *
 * @package WPAICB\Content
 */

namespace WPAICB\Content;

use WPAICB\Admin\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Chunker {

    /**
     * Target chunk size in words.
     *
     * @var int
     */
    private $chunk_size;

    /**
     * Overlap between chunks in words.
     *
     * @var int
     */
    private $overlap;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->chunk_size = (int) Admin::get_settings( 'chunk_size', 500 );
        $this->overlap    = max( 50, (int) ( $this->chunk_size * 0.1 ) ); // 10% overlap
    }

    /**
     * Split content into chunks.
     *
     * @param string $content Full text content.
     * @param string $title Post title for context.
     * @return array Array of chunk arrays with 'text', 'index', 'word_count'.
     */
    public function chunk( $content, $title = '' ) {
        if ( empty( trim( $content ) ) ) {
            return array();
        }

        $word_count = str_word_count( $content );

        // If content is small enough, return as single chunk
        if ( $word_count <= $this->chunk_size ) {
            $chunk_text = $title ? $title . "\n\n" . $content : $content;
            return array(
                array(
                    'text'       => $chunk_text,
                    'index'      => 0,
                    'word_count' => str_word_count( $chunk_text ),
                ),
            );
        }

        // Split into paragraphs first for natural boundaries
        $paragraphs = $this->split_into_paragraphs( $content );
        $chunks     = $this->merge_paragraphs_into_chunks( $paragraphs, $title );

        return $chunks;
    }

    /**
     * Split text into paragraphs.
     *
     * @param string $text Full text.
     * @return array Array of paragraph strings.
     */
    private function split_into_paragraphs( $text ) {
        // Split on double newlines (paragraph boundaries)
        $paragraphs = preg_split( '/\n{2,}/', $text );

        // Filter out empty paragraphs
        $paragraphs = array_filter( $paragraphs, function ( $p ) {
            return ! empty( trim( $p ) );
        } );

        return array_values( $paragraphs );
    }

    /**
     * Merge paragraphs into chunks respecting size limits.
     *
     * @param array  $paragraphs Array of paragraph strings.
     * @param string $title Post title for prepending.
     * @return array Array of chunk data.
     */
    private function merge_paragraphs_into_chunks( $paragraphs, $title ) {
        $chunks        = array();
        $current_chunk = '';
        $chunk_index   = 0;

        foreach ( $paragraphs as $paragraph ) {
            $paragraph_words = str_word_count( $paragraph );
            $current_words   = str_word_count( $current_chunk );

            // If a single paragraph exceeds chunk size, split it by sentences
            if ( $paragraph_words > $this->chunk_size ) {
                // Save current chunk if not empty
                if ( ! empty( trim( $current_chunk ) ) ) {
                    $chunk_text = $title ? $title . "\n\n" . trim( $current_chunk ) : trim( $current_chunk );
                    $chunks[]   = array(
                        'text'       => $chunk_text,
                        'index'      => $chunk_index,
                        'word_count' => str_word_count( $chunk_text ),
                    );
                    $chunk_index++;
                    $current_chunk = '';
                }

                // Split large paragraph by sentences
                $sentence_chunks = $this->split_by_sentences( $paragraph, $title, $chunk_index );
                foreach ( $sentence_chunks as $sc ) {
                    $chunks[] = $sc;
                    $chunk_index++;
                }
                continue;
            }

            // Check if adding this paragraph exceeds the limit
            if ( $current_words + $paragraph_words > $this->chunk_size && ! empty( trim( $current_chunk ) ) ) {
                // Save current chunk
                $chunk_text = $title ? $title . "\n\n" . trim( $current_chunk ) : trim( $current_chunk );
                $chunks[]   = array(
                    'text'       => $chunk_text,
                    'index'      => $chunk_index,
                    'word_count' => str_word_count( $chunk_text ),
                );
                $chunk_index++;

                // Start new chunk with overlap (last paragraph of previous chunk)
                $current_chunk = $paragraph . "\n\n";
            } else {
                $current_chunk .= $paragraph . "\n\n";
            }
        }

        // Don't forget the last chunk
        if ( ! empty( trim( $current_chunk ) ) ) {
            $chunk_text = $title ? $title . "\n\n" . trim( $current_chunk ) : trim( $current_chunk );
            $chunks[]   = array(
                'text'       => $chunk_text,
                'index'      => $chunk_index,
                'word_count' => str_word_count( $chunk_text ),
            );
        }

        return $chunks;
    }

    /**
     * Split a large paragraph by sentences.
     *
     * @param string $paragraph The paragraph text.
     * @param string $title Post title.
     * @param int    $start_index Starting chunk index.
     * @return array Array of chunk data.
     */
    private function split_by_sentences( $paragraph, $title, $start_index ) {
        $chunks    = array();
        $sentences = $this->split_sentences( $paragraph );

        $current_chunk = '';
        $chunk_index   = $start_index;

        foreach ( $sentences as $sentence ) {
            $current_words  = str_word_count( $current_chunk );
            $sentence_words = str_word_count( $sentence );

            if ( $current_words + $sentence_words > $this->chunk_size && ! empty( trim( $current_chunk ) ) ) {
                $chunk_text = $title ? $title . "\n\n" . trim( $current_chunk ) : trim( $current_chunk );
                $chunks[]   = array(
                    'text'       => $chunk_text,
                    'index'      => $chunk_index,
                    'word_count' => str_word_count( $chunk_text ),
                );
                $chunk_index++;
                $current_chunk = $sentence . ' ';
            } else {
                $current_chunk .= $sentence . ' ';
            }
        }

        if ( ! empty( trim( $current_chunk ) ) ) {
            $chunk_text = $title ? $title . "\n\n" . trim( $current_chunk ) : trim( $current_chunk );
            $chunks[]   = array(
                'text'       => $chunk_text,
                'index'      => $chunk_index,
                'word_count' => str_word_count( $chunk_text ),
            );
        }

        return $chunks;
    }

    /**
     * Split text into sentences.
     *
     * @param string $text Text to split.
     * @return array Array of sentences.
     */
    private function split_sentences( $text ) {
        // Split on sentence boundaries (period, question mark, exclamation)
        $sentences = preg_split( '/(?<=[.!?])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY );

        return array_filter( $sentences, function ( $s ) {
            return ! empty( trim( $s ) );
        } );
    }

    /**
     * Extract keywords from a chunk of text.
     *
     * @param string $text Chunk text.
     * @param array  $metadata Additional metadata (categories, tags).
     * @return string Comma-separated keywords.
     */
    public function extract_keywords( $text, $metadata = array() ) {
        // Get words, filter stop words, get most frequent
        $text  = strtolower( $text );
        $text  = preg_replace( '/[^\w\s]/', ' ', $text );
        $words = preg_split( '/\s+/', $text );

        $stop_words = $this->get_stop_words();

        // Count word frequency
        $word_freq = array();
        foreach ( $words as $word ) {
            $word = trim( $word );
            if ( strlen( $word ) < 3 || in_array( $word, $stop_words, true ) ) {
                continue;
            }
            if ( ! isset( $word_freq[ $word ] ) ) {
                $word_freq[ $word ] = 0;
            }
            $word_freq[ $word ]++;
        }

        // Sort by frequency
        arsort( $word_freq );

        // Take top 15 keywords
        $keywords = array_slice( array_keys( $word_freq ), 0, 15 );

        // Add metadata
        if ( ! empty( $metadata ) ) {
            foreach ( $metadata as $meta ) {
                $keywords[] = strtolower( $meta );
            }
        }

        return implode( ', ', array_unique( array_slice( $keywords, 0, 20 ) ) );
    }

    /**
     * Get common English stop words.
     *
     * @return array
     */
    private function get_stop_words() {
        return array(
            'a', 'an', 'the', 'is', 'it', 'to', 'in', 'for', 'on', 'with',
            'at', 'by', 'from', 'as', 'of', 'and', 'or', 'not', 'be', 'are',
            'was', 'were', 'been', 'being', 'have', 'has', 'had', 'do', 'does',
            'did', 'will', 'would', 'could', 'should', 'may', 'might', 'can',
            'this', 'that', 'these', 'those', 'me', 'my', 'we', 'you',
            'your', 'he', 'she', 'they', 'what', 'which', 'who', 'when',
            'where', 'why', 'how', 'all', 'each', 'every', 'both', 'few',
            'more', 'most', 'other', 'some', 'such', 'nor', 'only',
            'own', 'same', 'than', 'too', 'very', 'just', 'because',
            'but', 'about', 'out', 'then', 'there', 'here', 'also',
            'its', 'his', 'her', 'their', 'our', 'them', 'him',
            'into', 'over', 'after', 'before', 'between', 'under',
            'again', 'further', 'once', 'during', 'while', 'through',
        );
    }
}

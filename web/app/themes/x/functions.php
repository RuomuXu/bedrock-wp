<?php

add_action('acf/init', 'registerBlocks');
function registerBlocks() {
    if ( ! function_exists('register_block_type') ) {
        return;
    }

    $block_settings['attributes']['anchor'] = [
        'type' => 'string',
        'default' => '',
    ];

    $block_settings['supports'] = [
        "anchor" => true,
    ];

    $block_settings['render_callback'] = render_custom_block();

    foreach ($this->blocks as $block) {
        $block_path = get_template_directory()  . '/blocks/' . $block;
        register_block_type( $block_path , $block_settings );
    }
}

global $used_blocks_css;
$used_blocks_css = [];

function render_custom_block($attributes, $content, $block) {
    global $used_blocks_css;

    $block_name = $block['name']; // 获取块名称，例如 acf/articles-list
    $block_folder = get_template_directory() . '/blocks/' . str_replace('acf/', '', $block_name);
    $css_file = $block_folder . '/style.css';
    $template_file = $block_folder . str_replace('acf/', '', $block_name) . '.php';

    // 记录 CSS 文件路径
    if (file_exists($css_file) && !in_array($css_file, $used_blocks_css)) {
        $used_blocks_css[] = $css_file;
    }

    // 渲染块内容
    if (file_exists($template_file)) {
        include $template_file;
    }
}

function collect_used_blocks_css() {
    global $post;
    $used_blocks_css = [];

    // 确保是在前台且有内容的情况下
    if (is_singular() && !empty($post->post_content)) {
        // 解析页面的块
        $blocks = parse_blocks($post->post_content);

        foreach ($blocks as $block) {
            if (!empty($block['blockName'])) {
                // 获取块名称
                $block_name = $block['blockName']; // e.g., acf/articles-list
                
                // 获取对应的 CSS 文件路径
                $block_folder = get_template_directory() . '/blocks/' . str_replace('acf/', '', $block_name);
                $css_file = $block_folder . '/style.css';

                if (file_exists($css_file) && !in_array($css_file, $used_blocks_css)) {
                    $used_blocks_css[] = $css_file;
                }
            }
        }
    }

    return $used_blocks_css;
}

function output_inline_styles_for_blocks() {
    $cache_key = 'inline_block_styles_' . get_the_ID();
    $inline_css = get_transient($cache_key);

    if (!$inline_css) {
        $used_blocks_css = collect_used_blocks_css();

        $inline_css = '';
        if (!empty($used_blocks_css)) {
            foreach ($used_blocks_css as $css_file) {
                $css_content = file_get_contents($css_file);
                $inline_css .= $css_content;
            }
            $inline_css = minify_css($inline_css);
        }

        set_transient($cache_key, $inline_css, HOUR_IN_SECONDS);
    }

    if (!empty($inline_css)) {
        echo '<style id="inline-block-styles">' . $inline_css . '</style>';
    }
}
add_action('wp_head', 'output_inline_styles_for_blocks');

function minify_css($css) {
    return preg_replace('/\s+/', ' ', $css);
}
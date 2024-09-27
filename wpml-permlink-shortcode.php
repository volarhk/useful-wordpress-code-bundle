<?php

function wpml_translated_url($atts)
{
  extract(shortcode_atts(array(
    'lang' => 'en',
  ), $atts));

  // if the page is archived, get the translated url
  if (is_archive()) {
    $url = get_term_link(get_query_var('term'), get_query_var('taxonomy'));
    $url = apply_filters('wpml_permalink', $url, $lang);
    return $url;
  }

  // otherwise, get the translated url of the current page
  $url = get_permalink();
  $url = apply_filters('wpml_permalink', $url, $lang);
  return $url;
}

add_shortcode('wpml_translated_url', 'wpml_translated_url');


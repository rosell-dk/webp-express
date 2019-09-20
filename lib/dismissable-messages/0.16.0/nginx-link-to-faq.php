<?php

namespace WebPExpress;

/*echo '<p>You are running on NGINX. WebP Express works well on NGINX, however this UI is not streamlined NGINX yet. </p>' .
    '<p><b>You should head over to the </b>' .
    '<a href="https://wordpress.org/plugins/webp-express/#i%20am%20on%20nginx%20or%20openresty" target="_blank"><b>NGINX section in the FAQ</b></a>' .
    '<b> to learn how to use WebP Express on NGINX</b></p>';*/


DismissableMessages::printDismissableMessage(
    'warning',
    '<p>You are running on NGINX. WebP Express works well on NGINX, however this UI is not streamlined NGINX yet. </p>' .
        '<p><b>You should head over to the </b>' .
        '<a href="https://wordpress.org/plugins/webp-express/#i%20am%20on%20nginx%20or%20openresty" target="_blank"><b>NGINX section in the FAQ</b></a>' .
        '<b> to learn how to use WebP Express on NGINX</b></p>',
    '0.16.0/nginx-link-to-faq',
    'Got it!'
);

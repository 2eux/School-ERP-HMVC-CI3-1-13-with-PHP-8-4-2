<?php

public function set_security_headers() {
    header('Content-Security-Policy: default-src https:');
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Strict-Transport-Security: max-age=31536000');
}
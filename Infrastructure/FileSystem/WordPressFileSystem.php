<?php

namespace Gdatacyberdefenseag\WordpressGdataAntivirus\Infrastructure\FileSystem;

require_once ABSPATH . 'wp-admin/includes/file.php';

use WP_Filesystem_Base;

class WordPressFileSystem implements IGdataAntivirusFileSystem {
    private WP_Filesystem_Base $files_system;

    public function __construct() {
        \WP_Filesystem();
        global $wp_filesystem;
        $this->files_system = $wp_filesystem;
    }

    public function read( $path ): string {
        return $this->files_system->get_contents($path);
    }

    public function write( $path, $content ) {
        return $this->files_system->put_contents($path, $content);
    }

    public function delete( $path ) {
        return $this->files_system->delete($path);
    }

    public function is_writable( $path ): bool {
        return $this->files_system->is_writable($path);
    }
}

<?php
/**
 * Plugin Name: Cocoon Theme Manager
 * Description: Cocoonの親テーマおよび子テーマのインストール状態を確認し、管理画面から個別にインストール・再インストールできるプラグイン
 * Version: 2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// 管理画面に専用メニューを追加
add_action('admin_menu', function() {
    add_menu_page(
        'Cocoon インストーラー',
        'Cocoon インストール',
        'manage_options',
        'cocoon-installer',
        'cocoon_installer_page_html',
        'dashicons-download',
        61
    );
});

// 管理画面ページの出力とインストール処理
function cocoon_installer_page_html() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $themes_dir = WP_CONTENT_DIR . '/themes/';
    
    // 親テーマと子テーマの定義（提供された公式URL）
    $themes = [
        'parent' => [
            'name' => 'Cocoon 親テーマ',
            'url' => 'https://wp-cocoon.com/download/791/?tmstv=1740616710',
            'prefix' => 'cocoon-master'
        ],
        'child' => [
            'name' => 'Cocoon 子テーマ',
            'url' => 'https://wp-cocoon.com/download/726/?tmstv=1740616710',
            'prefix' => 'cocoon-child'
        ]
    ];

    // インストールボタンが押されたときの処理
    if (isset($_POST['install_cocoon_theme']) && check_admin_referer('cocoon_install_nonce')) {
        $target_key = sanitize_text_field($_POST['install_cocoon_theme']);
        if (isset($themes[$target_key])) {
            $theme_info = $themes[$target_key];
            $zip_url = $theme_info['url'];
            
            $upload_dir = wp_upload_dir();
            $zip_path = $upload_dir['basedir'] . '/cocoon-' . $target_key . '.zip';

            $zip_data = @file_get_contents($zip_url);
            if ($zip_data !== false) {
                file_put_contents($zip_path, $zip_data);
                $zip = new ZipArchive();
                if ($zip->open($zip_path) === TRUE) {
                    $zip->extractTo($themes_dir);
                    $zip->close();
                    unlink($zip_path);
                    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($theme_info['name']) . ' のインストールが完了しました！</p></div>';
                } else {
                    unlink($zip_path);
                    echo '<div class="notice notice-error is-dismissible"><p>ZIPファイルの解凍に失敗しました。</p></div>';
                }
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>ZIPファイルのダウンロードに失敗しました。</p></div>';
            }
        }
    }
    ?>
    <div class="wrap">
        <h1>Cocoon テーマ インストーラー</h1>
        <p>Cocoonの親テーマおよび子テーマのインストール状態を確認し、ボタンから直接インストールできます。</p>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>テーマ種類</th>
                    <th>フォルダ状態</th>
                    <th>インストール状態</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($themes as $key => $theme): 
                    $installed = false;
                    $found_name = '';
                    
                    // フォルダの有無をチェック
                    $dirs = glob($themes_dir . $theme['prefix'] . '*');
                    if (!empty($dirs)) {
                        $installed = true;
                        $found_name = basename($dirs[0]);
                    }
                ?>
                <tr>
                    <td><strong><?php echo esc_html($theme['name']); ?></strong></td>
                    <td><code><?php echo esc_html($found_name ? $found_name : $theme['prefix'] . '-*'); ?></code></td>
                    <td>
                        <?php if ($installed): ?>
                            <span style="color: #008a20; font-weight: bold;">✔ インストール済み</span>
                        <?php else: ?>
                            <span style="color: #d63638; font-weight: bold;">✖ 未インストール</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form method="post" style="display:inline;" <?php echo $installed ? 'onsubmit="return confirm(\'すでにインストールされています。上書きして再インストールしますか？\');"' : ''; ?>>
                            <?php wp_nonce_field('cocoon_install_nonce'); ?>
                            <input type="hidden" name="install_cocoon_theme" value="<?php echo esc_attr($key); ?>">
                            <?php submit_button($installed ? '再インストール' : 'インストール実行', $installed ? 'secondary small' : 'primary small', 'submit', false); ?>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}
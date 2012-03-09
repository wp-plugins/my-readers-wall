<?php
/*
  Plugin Name:My-Readers-Wall
  Plugin URI: http://outsiderla.me/my-readers-wall.html
  Description: 通过短代码的形式把你博客的读者列表出来，后台支持设置 读者 数量及样式...使用方法，新建一个页面，命名随意，在页面正文部分输入 “[readers]”发布就OK啦...
  Author: Outsider
  Version: 1.0.0
  Author URI: http://outsiderla.me/
 */
?>
<?php
register_activation_hook(__FILE__, 'readers_wall_install');
register_deactivation_hook(__FILE__, 'readers_wall_remove');

function readers_wall_install() {

    add_option("sh_r_limit", "50", '', 'yes');

    add_option("sh_r_color", "blue", '', 'yes');
}

function readers_wall_remove() {
    delete_option('sh_r_limit');

    delete_option('sh_r_color');
}

if (is_admin()) {
    add_action('admin_menu', 'display_r_menu');
}

function display_r_menu() {
    add_options_page('读者墙设置页面', '读者墙设置', 'administrator', 'readers_wall', 'display_r_html_page');
}

function display_r_html_page() {
    ?>
    <div>
        <h2>读者墙设置</h2>
        <form method="post" action="options.php">
            <?php wp_nonce_field('update-options'); ?>
            <p>
                显示最近的多少个读者:  <input type="text" name="sh_r_limit"   id="sh_r_limit" value="<?php echo get_option('sh_r_limit'); ?>" />
            </p>
            <p>
                鼠标移上去时字体颜色: <input type="text" name="sh_r_color"   id="sh_r_color" value="<?php echo get_option('sh_r_color'); ?>" />
            </p>
            <p>
                <input type="hidden" name="action" value="update" />
                <input type="hidden" name="page_options" value="sh_r_limit,sh_r_color" />
              
                <input type="submit" value="保存设置" class="button-primary" />
            </p>
        </form>
    </div>
    <?php
}

function readers_wall() {
    global $wpdb;
    $readers_limit = get_option('sh_r_limit');
    $query = "SELECT COUNT(comment_ID) AS cnt, comment_author, comment_author_url, comment_author_email FROM (SELECT * FROM $wpdb->comments LEFT OUTER JOIN $wpdb->posts ON ($wpdb->posts.ID=$wpdb->comments.comment_post_ID) WHERE comment_date > date_sub( NOW(), INTERVAL 24 MONTH ) AND user_id='0' AND comment_author_email !='5' AND post_password='' AND comment_approved='1' AND comment_type='') AS tempcmt GROUP BY comment_author_email ORDER BY cnt DESC LIMIT {$readers_limit}";
    $wall = $wpdb->get_results($query);

    $maxNum = $wall[0]->cnt;

    foreach ($wall as $comment) {

        $width = round(40 / ($maxNum / $comment->cnt), 2);

        if ($comment->comment_author_url)
            $url = $comment->comment_author_url;

        else
            $url = "#";
        $avatar = get_avatar($comment->comment_author_email, $size = '35', $default = get_bloginfo('url') . '/images/outsider.jpg');

        $tmp = "<li><a target=\"_blank\" href=\"" . $comment->comment_author_url . "\">" . $avatar . "<em>" . $comment->comment_author . "</em> <strong>+" . $comment->cnt . "</strong></br>" . $comment->comment_author_url . "</a></li>";

        $output .= $tmp;
    }

    $output = "<ul class=\"readers-list\">" . $output . "</ul>";

    return $output;
}

function readers_css() {
    $o_color = get_option('sh_r_color');
    echo "
	<style type='text/css'>
	.readers-list{line-height: 150%;text-align:left;overflow:hidden;_zoom:1}
    .readers-list li{width:200px;float:left;*margin-right:-1px}
    .readers-list a,.readers-list a:hover strong{background-color:#f2f2f2;background-image:-webkit-linear-gradient(#f8f8f8,#f2f2f2);background-image:-moz-linear-gradient(#f8f8f8,#f2f2f2);background-image:linear-gradient(#f8f8f8,#f2f2f2)}
    .readers-list a{position:relative;display:block;height:36px;margin:4px;padding:4px 4px 4px 44px;overflow:hidden;border:#ccc 1px solid;border-radius:2px;box-shadow:#eee 0 0 2px}
    .readers-list img,.readers-list em,.readers-list strong{-webkit-transition:all .2s ease-out;-moz-transition:all .2s ease-out;transition:all .2s ease-out}
    .readers-list img{width:36px;height:36px;float:left;margin:0 8px 0 -40px;border-radius:2px}
    .readers-list em{color:#666;font-style:normal;margin-right:10px}
    .readers-list strong{color:#ddd;width:40px;text-align:right;position:absolute;right:6px;top:4px;font:bold 14px/16px microsoft yahei}
    .readers-list a:hover{border-color:#bbb;box-shadow:#ccc 0 0 2px;background-color:#fff;background-image:none}
    .readers-list a:hover img{opacity:.6;margin-left:0}
    .readers-list a:hover em{color:{$o_color};font:bold 12px/36px microsoft yahei}
    .readers-list a:hover strong{color:{$o_color};right:150px;top:0;text-align:center;border-right:#ccc 1px solid;height:44px;line-height:40px}
	</style>
	";
}

add_action('wp_head', 'readers_css');
add_shortcode('readers', 'readers_wall');
?>
</div><!-- #content -->

<!-- ========== 页脚 ========== -->
<footer id="colophon" class="site-footer">

    <!-- 页脚小工具区 -->
    <?php if ( is_active_sidebar( 'footer-1' ) || is_active_sidebar( 'footer-2' ) || is_active_sidebar( 'footer-3' ) ) : ?>
    <div class="footer-widgets">
        <div class="container">
            <div class="footer-widgets-grid">
                <?php if ( is_active_sidebar( 'footer-1' ) ) : ?>
                <div class="footer-widget-col"><?php dynamic_sidebar( 'footer-1' ); ?></div>
                <?php endif; ?>
                <?php if ( is_active_sidebar( 'footer-2' ) ) : ?>
                <div class="footer-widget-col"><?php dynamic_sidebar( 'footer-2' ); ?></div>
                <?php endif; ?>
                <?php if ( is_active_sidebar( 'footer-3' ) ) : ?>
                <div class="footer-widget-col"><?php dynamic_sidebar( 'footer-3' ); ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- 友情链接 -->
    <div class="footer-links-bar">
        <div class="container">
            <div class="footer-links-row">
                <span class="footer-links-label">友情链接：</span>
                <a href="https://www.chengdu.gov.cn/" target="_blank" rel="noopener">成都市政府</a>
                <a href="http://hrss.chengdu.gov.cn/" target="_blank" rel="noopener">成都人社局</a>
                <a href="http://www.cdsgajg.gov.cn/" target="_blank" rel="noopener">成都交管局</a>
                <a href="https://www.tianfu.gov.cn/" target="_blank" rel="noopener">天府新区</a>
                <a href="https://www.cdmetro.cn/" target="_blank" rel="noopener">成都地铁</a>
            </div>
        </div>
    </div>

    <!-- 版权栏 -->
    <div class="footer-bottom">
        <div class="container">
            <div class="footer-bottom-inner">
                <div class="footer-copyright">
                    <span class="logo-icon">🐼</span>
                    <span>
                        © <?php echo date( 'Y' ); ?>
                        <a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a>
                        &nbsp;·&nbsp; 蜀ICP备XXXXXXXX号
                    </span>
                </div>
                <div class="footer-nav">
                    <?php
                    wp_nav_menu( [
                        'theme_location' => 'footer',
                        'menu_class'     => 'footer-nav-menu',
                        'container'      => false,
                        'depth'          => 1,
                        'fallback_cb'    => function() {
                            $items = [ '关于我们' => '/about', '联系我们' => '/contact', '隐私政策' => '/privacy', '服务条款' => '/terms' ];
                            echo '<ul class="footer-nav-menu">';
                            foreach ( $items as $l => $u ) {
                                echo '<li><a href="' . esc_url( home_url( $u ) ) . '">' . esc_html( $l ) . '</a></li>';
                            }
                            echo '</ul>';
                        },
                    ] );
                    ?>
                </div>
            </div>
        </div>
    </div>

</footer><!-- #colophon -->

</div><!-- #page -->

<?php wp_footer(); ?>
</body>
</html>

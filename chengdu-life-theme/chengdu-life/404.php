<?php get_header(); ?>
<main id="primary" class="site-main">
    <div class="container" style="padding:80px 0;text-align:center;">
        <div style="font-size:96px;margin-bottom:16px;">🐼</div>
        <h1 style="font-size:80px;font-weight:700;background:linear-gradient(135deg,#FF8C00,#FF5252);-webkit-background-clip:text;-webkit-text-fill-color:transparent;">404</h1>
        <h2 style="font-size:24px;color:var(--color-text-secondary);margin:16px 0 12px;">页面不见了</h2>
        <p style="color:var(--color-text-muted);margin-bottom:32px;">您访问的页面可能已被删除或地址有误，熊猫也找不到它。</p>
        <div style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap;">
            <a href="<?php echo esc_url( home_url() ); ?>" class="btn btn-primary">返回首页</a>
            <a href="javascript:history.back()" class="btn btn-outline">返回上一页</a>
        </div>
    </div>
</main>
<?php get_footer(); ?>

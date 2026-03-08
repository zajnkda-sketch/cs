/**
 * 成都生活圈主题 - 主 JavaScript
 * @package ChengduLife
 */
(function ($) {
    'use strict';

    /* ============================================================
       搜索浮层
       ============================================================ */
    const $searchBtn     = $('#headerSearchToggle');
    const $searchOverlay = $('#headerSearchOverlay');
    const $searchClose   = $('#searchClose');

    $searchBtn.on('click', function () {
        $searchOverlay.addClass('active');
        $searchOverlay.find('input[type="search"]').focus();
    });

    $searchClose.on('click', function () {
        $searchOverlay.removeClass('active');
    });

    $(document).on('keydown', function (e) {
        if (e.key === 'Escape') $searchOverlay.removeClass('active');
    });

    /* ============================================================
       移动端侧滑菜单
       ============================================================ */
    const $menuToggle  = $('#mobileMenuToggle');
    const $menuClose   = $('#mobileMenuClose');
    const $menuDrawer  = $('#mobileMenuDrawer');
    const $menuOverlay = $('#mobileMenuOverlay');

    function openMenu() {
        $menuDrawer.addClass('active');
        $menuOverlay.addClass('active');
        $('body').css('overflow', 'hidden');
    }

    function closeMenu() {
        $menuDrawer.removeClass('active');
        $menuOverlay.removeClass('active');
        $('body').css('overflow', '');
    }

    $menuToggle.on('click', openMenu);
    $menuClose.on('click', closeMenu);
    $menuOverlay.on('click', closeMenu);

    /* ============================================================
       导航高亮当前页
       ============================================================ */
    const currentUrl = window.location.href;
    $('.nav-menu a, .mobile-nav-menu a').each(function () {
        if ($(this).attr('href') === currentUrl) {
            $(this).closest('li').addClass('current-menu-item');
        }
    });

    /* ============================================================
       平滑滚动
       ============================================================ */
    $('a[href^="#"]').on('click', function (e) {
        const target = $(this.getAttribute('href'));
        if (target.length) {
            e.preventDefault();
            $('html, body').animate({ scrollTop: target.offset().top - 80 }, 400);
        }
    });

    /* ============================================================
       懒加载图片（IntersectionObserver）
       ============================================================ */
    if ('IntersectionObserver' in window) {
        const imgObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                    }
                    imgObserver.unobserve(img);
                }
            });
        }, { rootMargin: '200px' });

        $('img[data-src]').each(function () {
            imgObserver.observe(this);
        });
    }

    /* ============================================================
       回到顶部按钮
       ============================================================ */
    const $backTop = $('<button id="backToTop" aria-label="回到顶部"><i class="fas fa-chevron-up"></i></button>');
    $('body').append($backTop);

    $backTop.css({
        position: 'fixed', bottom: '32px', right: '24px',
        width: '44px', height: '44px', borderRadius: '50%',
        background: 'linear-gradient(135deg, #FF8C00, #FF5252)',
        color: '#fff', border: 'none', cursor: 'pointer',
        boxShadow: '0 4px 12px rgba(255,140,0,0.4)',
        display: 'none', alignItems: 'center', justifyContent: 'center',
        zIndex: 999, fontSize: '16px',
    });

    $(window).on('scroll', function () {
        if ($(this).scrollTop() > 400) {
            $backTop.css('display', 'flex');
        } else {
            $backTop.hide();
        }
    });

    $backTop.on('click', function () {
        $('html, body').animate({ scrollTop: 0 }, 400);
    });

    /* ============================================================
       侧边栏限行小组件 - 动态日期
       ============================================================ */
    function updateLimitWidget() {
        const $widget = $('.limit-widget-today');
        if (!$widget.length) return;

        const now  = new Date();
        const days = ['日', '一', '二', '三', '四', '五', '六'];
        const dateStr = now.getFullYear() + '年' +
                        (now.getMonth() + 1) + '月' +
                        now.getDate() + '日 ' +
                        '星期' + days[now.getDay()];

        $widget.find('.limit-widget-date').text(dateStr);
    }
    updateLimitWidget();

    /* ============================================================
       文章图片点击放大
       ============================================================ */
    $('.article-content img').on('click', function () {
        const src = $(this).attr('src');
        const $overlay = $('<div class="img-lightbox"><img src="' + src + '"><span class="img-lightbox-close">×</span></div>');
        $overlay.css({
            position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.85)',
            display: 'flex', alignItems: 'center', justifyContent: 'center',
            zIndex: 9999, cursor: 'zoom-out',
        });
        $overlay.find('img').css({ maxWidth: '90vw', maxHeight: '90vh', borderRadius: '8px' });
        $overlay.find('.img-lightbox-close').css({
            position: 'absolute', top: '20px', right: '24px',
            color: '#fff', fontSize: '32px', cursor: 'pointer', lineHeight: 1,
        });
        $('body').append($overlay);
        $overlay.on('click', function () { $overlay.remove(); });
    });

})(jQuery);

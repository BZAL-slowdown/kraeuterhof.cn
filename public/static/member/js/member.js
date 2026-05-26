/**
 * 用户中心前台脚本：侧栏（移动/桌面）、菜单折叠、回顶等
 * 依赖：jQuery（在 mheader 中先于本文件加载）
 */

/**
 * Metronic 全局 `App` 的兜底（部分模块模板内联脚本会写：
 *   if (App.isAngularJsApp() === false) { ... 初始化 datepicker 等 }
 * 未引入 `app.min.js` / `layout.js` 时 `App` 不存在会抛 ReferenceError）
 *
 * 官方语义（与 Rw layout.js 一致）：是否已加载 AngularJS——
 *   `typeof angular !== 'undefined'` 为 true 则认为当前是 Angular 应用页；
 *   会员中心一般为 false，走 jQuery 插件初始化分支。
 *
 * `isRTL()`：与 Metronic 一致，表示是否 RTL 布局（一般看 body 的 CSS direction / dir）。
 * `getViewPort` / `getResponsiveBreakpoint`：部分插件会调用，缺省时给最小实现以免连环报错。
 */
(function (w) {
    var $ = w.jQuery;

    if (typeof w.App === 'undefined') {
        w.App = {};
    }

    if (typeof w.App.isAngularJsApp !== 'function') {
        w.App.isAngularJsApp = function () {
            return typeof w.angular !== 'undefined';
        };
    }

    if (typeof w.App.isRTL !== 'function') {
        w.App.isRTL = function () {
            try {
                if ($ && $.fn && $('body').length) {
                    return $('body').css('direction') === 'rtl';
                }
            } catch (e) {}
            var doc = w.document;
            if (!doc) {
                return false;
            }
            var de = doc.documentElement;
            var b = doc.body;
            var dir = (de && de.getAttribute('dir')) || (b && b.getAttribute('dir')) || '';
            return String(dir).toLowerCase() === 'rtl';
        };
    }

    if (typeof w.App.getViewPort !== 'function') {
        w.App.getViewPort = function () {
            var e = w;
            var a = 'inner';
            if (!('innerWidth' in w)) {
                a = 'client';
                e = w.document.documentElement || w.document.body;
            }
            return {
                width: e[a + 'Width'],
                height: e[a + 'Height']
            };
        };
    }

    if (typeof w.App.getResponsiveBreakpoint !== 'function') {
        w.App.getResponsiveBreakpoint = function (size) {
            var sizes = { xs: 480, sm: 768, md: 992, lg: 1200 };
            return sizes[size] ? sizes[size] : 0;
        };
    }
})(window);

(function ($, window, document) {
    'use strict';

    var NS = 'mcMember';
    var BREAKPOINT_MOBILE = 991;
    var STORAGE_NARROW = 'mc_sidebar_narrow';

    function isMobileViewport() {
        return window.matchMedia('(max-width: ' + BREAKPOINT_MOBILE + 'px)').matches;
    }

    function openMobileSidebar() {
        $('body').addClass('mc-sidebar-mobile-open');
        $('.page-sidebar.navbar-collapse').addClass('in');
    }

    function closeMobileSidebar() {
        $('body').removeClass('mc-sidebar-mobile-open');
        $('.page-sidebar.navbar-collapse').removeClass('in show');
    }

    function toggleMobileSidebar() {
        if ($('body').hasClass('mc-sidebar-mobile-open')) {
            closeMobileSidebar();
        } else {
            openMobileSidebar();
        }
    }

    /** 顶栏「汉堡」：移动端抽屉侧栏 */
    function bindResponsiveToggler() {
        $(document).on('click.' + NS, '.responsive-toggler', function (e) {
            e.preventDefault();
            e.stopPropagation();
            toggleMobileSidebar();
        });
    }

    /** 点击主区域或遮罩关闭移动端侧栏 */
    function bindOutsideClose() {
        $(document).on('click.' + NS, function (e) {
            if (!isMobileViewport()) {
                return;
            }
            if (!$('body').hasClass('mc-sidebar-mobile-open')) {
                return;
            }
            var $t = $(e.target);
            if ($t.closest('.page-sidebar-wrapper').length) {
                return;
            }
            if ($t.closest('.responsive-toggler').length) {
                return;
            }
            /* 顶栏内操作（通知、用户菜单）不关闭侧栏 */
            if ($t.closest('.page-header').length) {
                return;
            }
            closeMobileSidebar();
        });
    }

    function bindEscape() {
        $(document).on('keydown.' + NS, function (e) {
            if (e.keyCode !== 27) {
                return;
            }
            if ($('body').hasClass('mc-sidebar-mobile-open')) {
                closeMobileSidebar();
            }
        });
    }

    /** 窗口变大时收起移动侧栏，避免状态错乱 */
    function bindResize() {
        var mq = window.matchMedia('(max-width: ' + BREAKPOINT_MOBILE + 'px)');
        function onChange() {
            if (!mq.matches) {
                closeMobileSidebar();
            }
        }
        if (mq.addEventListener) {
            mq.addEventListener('change', onChange);
        } else if (mq.addListener) {
            mq.addListener(onChange);
        }
    }

    /** 桌面侧栏折叠（窄栏图标模式），状态写入 localStorage */
    function bindDesktopSidebarToggler() {
        try {
            if (window.localStorage && window.localStorage.getItem(STORAGE_NARROW) === '1') {
                $('body').addClass('mc-sidebar-narrow');
            }
        } catch (err) {}

        $(document).on('click.' + NS, '.menu-toggler.sidebar-toggler', function (e) {
            e.preventDefault();
            e.stopPropagation();
            if (isMobileViewport()) {
                return;
            }
            $('body').toggleClass('mc-sidebar-narrow');
            try {
                if (window.localStorage) {
                    window.localStorage.setItem(
                        STORAGE_NARROW,
                        $('body').hasClass('mc-sidebar-narrow') ? '1' : '0'
                    );
                }
            } catch (err) {}
        });
    }

    /**
     * 一级菜单：带 sub-menu 且链接为 javascript: 时，展开/收起
     * 同级仅保留一项展开（手风琴）
     */
    function bindSidebarAccordion() {
        $(document).on('click.' + NS, '.page-sidebar-menu > .nav-item > .nav-link.nav-toggle', function (e) {
            var href = ($(this).attr('href') || '').trim();
            if (href.indexOf('javascript:') !== 0 && href !== '#') {
                return;
            }
            var $li = $(this).closest('.nav-item');
            var $sub = $li.children('.sub-menu');
            if (!$sub.length) {
                return;
            }
            e.preventDefault();
            var willOpen = !$li.hasClass('open');
            /* 与 Metronic 一致：手风琴只保留一项展开；必须同时去掉 active，
               否则「用户中心」等由服务端带上 active 的项在切换后仍保持主轨高亮 */
            $li.siblings('.nav-item').removeClass('open active');
            $li.toggleClass('open', willOpen);
        });
    }

    /** 回顶 */
    function bindScrollToTop() {
        $(document).on('click.' + NS, '.page-footer .scroll-to-top, .scroll-to-top', function (e) {
            e.preventDefault();
            $('html, body').stop().animate({ scrollTop: 0 }, 360);
        });
    }

    /** 无 Bootstrap 时，顶栏下拉简单切换（有 Bootstrap 时其会处理，此处不冲突则作为补充） */
    function bindHeaderDropdownFallback() {
        if (typeof $.fn.dropdown === 'function') {
            return;
        }
        $(document).on('click.' + NS, '.page-header .dropdown-toggle', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var $li = $(this).parent();
            var open = $li.hasClass('open');
            $('.page-header .navbar-nav > li').removeClass('open');
            if (!open) {
                $li.addClass('open');
            }
        });
        $(document).on('click.' + NS, function () {
            $('.page-header .navbar-nav > li').removeClass('open');
        });
        $(document).on('click.' + NS, '.page-header .dropdown-menu', function (e) {
            e.stopPropagation();
        });
    }

    function init() {
        bindResponsiveToggler();
        bindOutsideClose();
        bindEscape();
        bindResize();
        bindDesktopSidebarToggler();
        bindSidebarAccordion();
        bindScrollToTop();
        bindHeaderDropdownFallback();
    }

    if (document.readyState === 'loading') {
        $(document).ready(init);
    } else {
        init();
    }

    window.McMemberUI = {
        closeMobileSidebar: closeMobileSidebar,
        openMobileSidebar: openMobileSidebar,
        isMobileViewport: isMobileViewport
    };
})(jQuery, window, document);

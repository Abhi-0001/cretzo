<div class="overview-side-container">
    <h1 class="heading-b">Account</h1>
    <?php
    /*
     * Every other my-account page sets $users to the logged-in user OBJECT
     * ($this->ion_auth->user()->row()), but My_account::chat() sets it to an ARRAY of chat
     * contacts - so `$users->username` here threw "Attempt to read property username on
     * array". The logged-in user is already available as $user (set in the controller's
     * constructor); use that instead. Guarded because it is [] when not logged in.
     */
    ?>
    <p class="text-n"><?= (is_object($user) && isset($user->username)) ? $user->username : '' ?></p>
    <div class="overview-container">

        <?php $this->load->view('front-end/' . THEME . '/partials/my-account-sidebar', ['active_menu' => $main_page]); ?>

        <div class="overview-right">

            <div class="cs-chat-soon">
                <div class="cs-chat-soon__badge">
                    <i class="uil uil-comments-alt"></i>
                    <span class="cs-chat-soon__pulse"></span>
                </div>
                <span class="cs-chat-soon__chip">Coming Soon</span>
                <h2 class="cs-chat-soon__title">Live Chat</h2>
                <p class="cs-chat-soon__text">We’re working on launching our <strong>Live Chat Support</strong> feature to provide you with a faster and smoother support experience.</p>
                <?php
                /*
                 * Until live chat ships this page was a dead end - it announced the feature and
                 * offered no way to actually reach anybody. WhatsApp is the live human channel,
                 * so it gets the primary action here (whatsapp_support_link() resolves the number
                 * from settings, falling back to the confirmed one).
                 */
                $whatsapp_link = whatsapp_support_link('Hello Cretzo Support, I need help with my order.');
                ?>
                <?php if (!empty($whatsapp_link)) { ?>
                    <p class="cs-chat-soon__text">In the meantime our team is on WhatsApp and replies during business hours.</p>
                    <a href="<?= html_escape($whatsapp_link) ?>" target="_blank" rel="noopener" class="cs-chat-soon__wa">
                        <i class="uil uil-whatsapp"></i> Chat on WhatsApp
                    </a>
                <?php } ?>
                <p class="cs-chat-soon__text">Prefer a written trail? <a href="<?= base_url('my-account/support') ?>">Raise a support ticket</a>.</p>
            </div>

        </div>
    </div>
</div>

<style>
    .cs-chat-soon {
        max-width: 560px;
        margin: 8px auto 0;
        padding: 48px 32px 44px;
        text-align: center;
        background: transparent;
        border: none;
        border-radius: 0;
        box-shadow: none;
    }
    .cs-chat-soon__badge {
        position: relative;
        width: 84px;
        height: 84px;
        margin: 0 auto 22px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        color: #fff;
        font-size: 38px;
        background: linear-gradient(135deg, #ff9a3d 0%, #ff7a1a 100%);
        box-shadow: 0 14px 30px -8px rgba(255, 122, 26, .55);
    }
    .cs-chat-soon__pulse {
        position: absolute;
        inset: 0;
        border-radius: 50%;
        border: 2px solid rgba(255, 122, 26, .45);
        animation: csChatPulse 2s ease-out infinite;
    }
    @keyframes csChatPulse {
        0%   { transform: scale(1);   opacity: .7; }
        100% { transform: scale(1.5); opacity: 0; }
    }
    .cs-chat-soon__chip {
        display: inline-block;
        margin-bottom: 12px;
        padding: 6px 16px;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .12em;
        text-transform: uppercase;
        color: #ff7a1a;
        background: #fff2e8;
        border-radius: 999px;
    }
    .cs-chat-soon__title {
        margin: 0 0 18px;
        font-size: 28px;
        font-weight: 700;
        color: #1f2937;
    }
    .cs-chat-soon__text {
        margin: 0 auto 14px;
        max-width: 440px;
        font-size: 15px;
        line-height: 1.7;
        color: #6b7280;
    }
    .cs-chat-soon__text strong {
        color: #374151;
        font-weight: 600;
    }
    .cs-chat-soon__wa {
        display: inline-flex;
        align-items: center;
        gap: 9px;
        margin: 6px 0 18px;
        padding: 13px 30px;
        font-size: 15px;
        font-weight: 600;
        color: #fff;
        background: #25d366;
        border-radius: 999px;
        text-decoration: none;
        box-shadow: 0 10px 24px -10px rgba(37, 211, 102, .75);
        transition: transform .15s ease, box-shadow .15s ease;
    }
    .cs-chat-soon__wa:hover,
    .cs-chat-soon__wa:focus {
        color: #fff;
        text-decoration: none;
        transform: translateY(-1px);
        box-shadow: 0 14px 28px -10px rgba(37, 211, 102, .85);
    }
    .cs-chat-soon__wa i {
        font-size: 20px;
        line-height: 1;
    }
</style>

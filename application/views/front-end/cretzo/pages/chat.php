<div class="overview-side-container">
    <h1 class="heading-b">Account</h1>
    <p class="text-n"><?= $users->username ?></p>
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
                <p class="cs-chat-soon__text">For now, you can get instant assistance through our <strong>AI Assistant</strong> or connect with us via <strong>WhatsApp Support</strong> using the chatbot in the bottom-right corner.</p>
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
</style>

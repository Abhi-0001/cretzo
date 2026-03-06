<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h4>Subscription Plans</h4>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('seller/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Subscriptions</li>
                    </ol>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </section>

    <section class="content">
        <div class="container-fluid">
            <style>
        :root {
            --bg-cream: #FFF9E6;
            --bg-accent: #FFE5CC;
            --orange: #F28C38;
            --card-yellow: #FEF4C1;
            --text: #333;
        }

        .subscription-page-body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-cream);
            color: var(--text);
            text-align: center;
        }

        .subscription-header { padding: 40px 20px; }
        h1 { font-size: 32px; margin-bottom: 5px; }
        .subtitle { color: var(--orange); font-weight: 600; margin-bottom: 40px; }

        /* Plans Logic Styling */
        .subscription-plans-container {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
            padding: 20px;
        }

        .subscription-card {
            background-color: var(--card-yellow);
            border-radius: 15px;
            width: 250px;
            max-width: 100%;
            padding: 30px 20px;
            position: relative;
            transition: transform 0.2s;
            border: 2px solid transparent;
            margin-bottom: 20px;
        }

        /* Active Plan Highlight */
        .subscription-card.active {
            border-color: var(--orange);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        .active-badge {
            display: none;
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--orange);
            color: white;
            padding: 2px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .subscription-card.active .active-badge { display: block; }

        .subscription-card h2 { color: var(--orange); margin-top: 0; }
        .price { font-size: 24px; font-weight: bold; margin: 15px 0; }
        .listings { font-weight: bold; }
        
        .upgrade-btn {
            margin-top: 20px;
            background: var(--orange);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }

        .upgrade-btn:disabled { background: #ccc; cursor: not-allowed; }

        /* Commission Section */
        .subscription-commission-sec {
            background-color: var(--bg-accent);
            padding: 60px 20px;
            margin-top: 40px;
        }

        .subscription-table-box {
            background: #fffcf2;
            max-width: 600px;
            margin: 0 auto;
            border-radius: 10px;
            padding: 20px;
            text-align: left;
        }

        table { width: 100%; border-collapse: collapse; }
        th { border-bottom: 1px solid #ddd; padding: 10px; font-size: 14px; }
        td { padding: 12px 10px; }
        .text-right { text-align: right; }

        .subscription-know-more {
            background: var(--orange);
            color: white;
            border: none;
            padding: 10px 30px;
            border-radius: 20px;
            margin-top: 30px;
            cursor: pointer;
        }
            </style>

            <div class="card">
                <div class="card-body subscription-page-body">
                    <section class="subscription-header">
                        <h1>Subscription Plans</h1>
                        <p class="subtitle">Choose a plan that fits your creative journey</p>

                        <div class="subscription-plans-container">
            <div class="subscription-card" id="plan-basic">
                <div class="active-badge">CURRENT PLAN</div>
                <h2>Basic</h2>
                <div class="price">Free</div>
                <div class="listings">Up to 50 Listings</div>
                <div class="validity">Lifetime</div>
                <button class="upgrade-btn" onclick="upgradePlan('basic')">Active</button>
            </div>

            <div class="subscription-card" id="plan-standard">
                <div class="active-badge">CURRENT PLAN</div>
                <h2>Standard</h2>
                <div class="price">399/-</div>
                <div class="listings">100 extra listings</div>
                <div class="validity">Up to 150 listings</div>
                <button class="upgrade-btn" onclick="upgradePlan('standard')">Upgrade</button>
            </div>

            <div class="subscription-card" id="plan-premium">
                <div class="active-badge">CURRENT PLAN</div>
                <h2>Premium</h2>
                <div class="price">999/-</div>
                <div class="listings">Unlimited</div>
                <div class="validity">Valid up to one year</div>
                <button class="upgrade-btn" onclick="upgradePlan('premium')">Upgrade</button>
            </div>
                        </div>
                    </section>

                    <section class="subscription-commission-sec">
                        <h1 style="color: var(--orange); font-size: 40px;">Commission</h1>
                        <div class="subscription-table-box">
            <table>
                <thead>
                    <tr>
                        <th>Orders</th>
                        <th class="text-right">Commission % per transaction</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>For first 50 Order</td><td class="text-right">8%</td></tr>
                    <tr><td>51 - 100 Order</td><td class="text-right">10%</td></tr>
                    <tr><td>Commission after 100 orders</td><td class="text-right">12%</td></tr>
                </tbody>
            </table>
                        </div>
                        <button class="subscription-know-more">Know more</button>
                        <p style="font-size: 12px; margin-top: 10px;">Or talk to our customer support</p>
                    </section>
                </div>
            </div>

            <script>
        // Simple state management for active plan
        let currentPlan = 'basic';

        function updateUI() {
            // Remove active status from all subscription cards
            document.querySelectorAll('.subscription-card').forEach(card => card.classList.remove('active'));
            document.querySelectorAll('.upgrade-btn').forEach(btn => {
                btn.innerText = 'Upgrade';
                btn.disabled = false;
            });

            // Set current plan status
            const activeCard = document.getElementById(`plan-${currentPlan}`);
            activeCard.classList.add('active');
            
            const activeBtn = activeCard.querySelector('.upgrade-btn');
            activeBtn.innerText = 'Active';
            activeBtn.disabled = true;
        }

        function upgradePlan(planId) {
            // Logic for upgrading (e.g., redirect to payment)
            const confirmUpgrade = confirm(`Do you want to switch to the ${planId} plan?`);
            if(confirmUpgrade) {
                currentPlan = planId;
                updateUI();
                alert(`Successfully upgraded to ${planId.toUpperCase()}!`);
            }
        }

        // Initialize view
        updateUI();
            </script>
        </div>
    </section>
</div>

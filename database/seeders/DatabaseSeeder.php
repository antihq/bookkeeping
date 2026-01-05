<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::factory()->withoutTwoFactor()->withPersonalTeam()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $team = $user->personalTeam();

        $categories = $this->createCategories($team);
        $accounts = $this->createAccounts($team, $user);
        $this->createTransactions($team, $user, $accounts, $categories);
    }

    private function createCategories($team): array
    {
        $categories = [
            'Salary',
            'Freelance Income',
            'Investment Returns',
            'Groceries',
            'Dining & Restaurants',
            'Transportation',
            'Utilities',
            'Rent',
            'Insurance',
            'Entertainment',
            'Shopping',
            'Healthcare',
            'Education',
            'Travel',
            'Gifts',
            'Subscriptions',
            'ATM Withdrawal',
            'Transfer',
        ];

        $categoryModels = [];
        foreach ($categories as $name) {
            $categoryModels[$name] = Category::create([
                'team_id' => $team->id,
                'name' => $name,
            ]);
        }

        return $categoryModels;
    }

    private function createAccounts($team, $user): array
    {
        $accounts = [];

        $accounts[] = Account::create([
            'team_id' => $team->id,
            'created_by' => $user->id,
            'type' => 'checking',
            'name' => 'Main Checking Account',
            'currency' => 'usd',
            'start_balance' => 500000,
        ]);

        $accounts[] = Account::create([
            'team_id' => $team->id,
            'created_by' => $user->id,
            'type' => 'savings',
            'name' => 'Emergency Fund',
            'currency' => 'usd',
            'start_balance' => 2500000,
        ]);

        $accounts[] = Account::create([
            'team_id' => $team->id,
            'created_by' => $user->id,
            'type' => 'savings',
            'name' => 'Vacation Savings',
            'currency' => 'usd',
            'start_balance' => 800000,
        ]);

        $accounts[] = Account::create([
            'team_id' => $team->id,
            'created_by' => $user->id,
            'type' => 'credit card',
            'name' => 'Chase Sapphire',
            'currency' => 'usd',
            'start_balance' => -125000,
        ]);

        $accounts[] = Account::create([
            'team_id' => $team->id,
            'created_by' => $user->id,
            'type' => 'credit card',
            'name' => 'Amex Gold',
            'currency' => 'usd',
            'start_balance' => -45000,
        ]);

        $accounts[] = Account::create([
            'team_id' => $team->id,
            'created_by' => $user->id,
            'type' => 'cash',
            'name' => 'Wallet Cash',
            'currency' => 'usd',
            'start_balance' => 25000,
        ]);

        $accounts[] = Account::create([
            'team_id' => $team->id,
            'created_by' => $user->id,
            'type' => 'checking',
            'name' => 'Joint Checking',
            'currency' => 'usd',
            'start_balance' => 325000,
        ]);

        return $accounts;
    }

    private function createTransactions($team, $user, $accounts, $categories): void
    {
        $checking = $accounts[0];
        $savings = $accounts[1];
        $vacationSavings = $accounts[2];
        $chaseCard = $accounts[3];
        $amexCard = $accounts[4];
        $cash = $accounts[5];
        $jointChecking = $accounts[6];

        $baseDate = Carbon::now()->subMonths(6);

        $this->createIncomeTransactions($checking, $baseDate, $user, $team, $categories);

        $this->createRecurringExpenses($checking, $chaseCard, $baseDate, $user, $team, $categories);

        $this->createDailyExpenses($checking, $chaseCard, $amexCard, $cash, $baseDate, $user, $team, $categories);

        $this->createTransfersAndEdgeCases($checking, $savings, $vacationSavings, $jointChecking, $cash, $baseDate, $user, $team, $categories);

        $this->createCreditCardPayments($checking, $chaseCard, $amexCard, $baseDate, $user, $team, $categories);

        $this->createUncategorizedTransactions($checking, $baseDate, $user, $team);

        $this->createLargeTransactions($checking, $baseDate, $user, $team, $categories);
    }

    private function createIncomeTransactions($account, $baseDate, $user, $team, $categories): void
    {
        for ($i = 0; $i < 6; $i++) {
            $date = $baseDate->copy()->addMonths($i)->day(15);
            Transaction::create([
                'account_id' => $account->id,
                'team_id' => $team->id,
                'created_by' => $user->id,
                'date' => $date->format('Y-m-d'),
                'payee' => 'Employer Direct Deposit',
                'amount' => 850000,
                'note' => 'Monthly salary',
                'category_id' => $categories['Salary']->id,
            ]);
        }

        Transaction::create([
            'account_id' => $account->id,
            'team_id' => $team->id,
            'created_by' => $user->id,
            'date' => $baseDate->copy()->addMonth()->day(20)->format('Y-m-d'),
            'payee' => 'Client Payment - XYZ Corp',
            'amount' => 250000,
            'note' => 'Freelance project',
            'category_id' => $categories['Freelance Income']->id,
        ]);

        Transaction::create([
            'account_id' => $account->id,
            'team_id' => $team->id,
            'created_by' => $user->id,
            'date' => $baseDate->copy()->addMonths(3)->day(10)->format('Y-m-d'),
            'payee' => 'Brokerage Account',
            'amount' => 12500,
            'note' => 'Dividend payment',
            'category_id' => $categories['Investment Returns']->id,
        ]);
    }

    private function createRecurringExpenses($checking, $creditCard, $baseDate, $user, $team, $categories): void
    {
        $expenses = [
            ['payee' => 'Landlord', 'amount' => -200000, 'category' => 'Rent', 'account' => $checking],
            ['payee' => 'Electric Company', 'amount' => -15000, 'category' => 'Utilities', 'account' => $checking],
            ['payee' => 'Gas Company', 'amount' => -8500, 'category' => 'Utilities', 'account' => $checking],
            ['payee' => 'Internet Provider', 'amount' => -7999, 'category' => 'Utilities', 'account' => $checking],
            ['payee' => 'Phone Company', 'amount' => -8500, 'category' => 'Utilities', 'account' => $checking],
            ['payee' => 'Netflix', 'amount' => -1599, 'category' => 'Subscriptions', 'account' => $creditCard],
            ['payee' => 'Spotify', 'amount' => -999, 'category' => 'Subscriptions', 'account' => $creditCard],
            ['payee' => 'Gym Membership', 'amount' => -4999, 'category' => 'Subscriptions', 'account' => $creditCard],
            ['payee' => 'Insurance Provider', 'amount' => -12500, 'category' => 'Insurance', 'account' => $checking],
            ['payee' => 'Car Insurance', 'amount' => -10000, 'category' => 'Insurance', 'account' => $creditCard],
        ];

        foreach ($expenses as $expense) {
            for ($i = 0; $i < 6; $i++) {
                $date = $baseDate->copy()->addMonths($i)->day(random_int(1, 5));
                Transaction::create([
                    'account_id' => $expense['account']->id,
                    'team_id' => $team->id,
                    'created_by' => $user->id,
                    'date' => $date->format('Y-m-d'),
                    'payee' => $expense['payee'],
                    'amount' => $expense['amount'],
                    'category_id' => $categories[$expense['category']]->id,
                ]);
            }
        }
    }

    private function createDailyExpenses($checking, $chaseCard, $amexCard, $cash, $baseDate, $user, $team, $categories): void
    {
        $dailyExpenseTemplates = [
            ['payee' => 'Whole Foods', 'amount' => [-8500, -15000, -22000], 'category' => 'Groceries', 'account' => $chaseCard],
            ['payee' => 'Trader Joes', 'amount' => [-6500, -12000, -18000], 'category' => 'Groceries', 'account' => $amexCard],
            ['payee' => 'CVS Pharmacy', 'amount' => [-2500, -4500, -8500], 'category' => 'Healthcare', 'account' => $checking],
            ['payee' => 'Starbucks', 'amount' => [-650, -850, -1050], 'category' => 'Dining & Restaurants', 'account' => $chaseCard],
            ['payee' => 'Local Cafe', 'amount' => [-1200, -1800, -2500], 'category' => 'Dining & Restaurants', 'account' => $cash],
            ['payee' => 'Gas Station', 'amount' => [-3500, -5500, -7500], 'category' => 'Transportation', 'account' => $chaseCard],
            ['payee' => 'Uber', 'amount' => [-1500, -2500, -4500], 'category' => 'Transportation', 'account' => $amexCard],
            ['payee' => 'Amazon', 'amount' => [-2500, -15000, -75000], 'category' => 'Shopping', 'account' => $amexCard],
            ['payee' => 'Target', 'amount' => [-4500, -25000, -55000], 'category' => 'Shopping', 'account' => $chaseCard],
            ['payee' => 'Restaurant', 'amount' => [-3500, -6500, -12000], 'category' => 'Dining & Restaurants', 'account' => $chaseCard],
            ['payee' => 'Movie Theater', 'amount' => [-2500, -3500], 'category' => 'Entertainment', 'account' => $amexCard],
            ['payee' => 'Concert Tickets', 'amount' => [-8500, -15000, -25000], 'category' => 'Entertainment', 'account' => $chaseCard],
        ];

        for ($day = 0; $day < 180; $day++) {
            $currentDate = $baseDate->copy()->addDays($day);
            $numTransactions = random_int(1, 3);

            for ($t = 0; $t < $numTransactions; $t++) {
                $template = $dailyExpenseTemplates[array_rand($dailyExpenseTemplates)];
                $amount = $template['amount'][array_rand($template['amount'])];

                Transaction::create([
                    'account_id' => $template['account']->id,
                    'team_id' => $team->id,
                    'created_by' => $user->id,
                    'date' => $currentDate->format('Y-m-d'),
                    'payee' => $template['payee'],
                    'amount' => $amount,
                    'note' => random_int(0, 1) ? null : 'Regular purchase',
                    'category_id' => $categories[$template['category']]->id,
                ]);
            }
        }
    }

    private function createTransfersAndEdgeCases($checking, $savings, $vacationSavings, $jointChecking, $cash, $baseDate, $user, $team, $categories): void
    {
        $transfers = [
            ['from' => $checking, 'to' => $savings, 'amount' => -100000, 'category' => 'Transfer'],
            ['from' => $checking, 'to' => $vacationSavings, 'amount' => -50000, 'category' => 'Transfer'],
            ['from' => $checking, 'to' => $jointChecking, 'amount' => -75000, 'category' => 'Transfer'],
            ['from' => $savings, 'to' => $checking, 'amount' => 150000, 'category' => 'Transfer'],
        ];

        foreach ($transfers as $transfer) {
            for ($i = 0; $i < 2; $i++) {
                $date = $baseDate->copy()->addMonths($i * 3)->day(random_int(10, 20));

                Transaction::create([
                    'account_id' => $transfer['from']->id,
                    'team_id' => $team->id,
                    'created_by' => $user->id,
                    'date' => $date->format('Y-m-d'),
                    'payee' => 'Transfer to ' . $transfer['to']->name,
                    'amount' => $transfer['amount'],
                    'note' => 'Monthly transfer',
                    'category_id' => $categories[$transfer['category']]->id,
                ]);

                Transaction::create([
                    'account_id' => $transfer['to']->id,
                    'team_id' => $team->id,
                    'created_by' => $user->id,
                    'date' => $date->copy()->addDay()->format('Y-m-d'),
                    'payee' => 'Transfer from ' . $transfer['from']->name,
                    'amount' => abs($transfer['amount']),
                    'note' => 'Monthly transfer',
                    'category_id' => $categories[$transfer['category']]->id,
                ]);
            }
        }

        Transaction::create([
            'account_id' => $checking->id,
            'team_id' => $team->id,
            'created_by' => $user->id,
            'date' => $baseDate->copy()->addWeeks(2)->format('Y-m-d'),
            'payee' => 'ATM Withdrawal',
            'amount' => -50000,
            'note' => 'Cash withdrawal',
            'category_id' => $categories['ATM Withdrawal']->id,
        ]);

        Transaction::create([
            'account_id' => $cash->id,
            'team_id' => $team->id,
            'created_by' => $user->id,
            'date' => $baseDate->copy()->addWeeks(2)->format('Y-m-d'),
            'payee' => 'ATM Deposit',
            'amount' => 50000,
            'note' => 'Cash withdrawal',
            'category_id' => $categories['ATM Withdrawal']->id,
        ]);

        Transaction::create([
            'account_id' => $checking->id,
            'team_id' => $team->id,
            'created_by' => $user->id,
            'date' => $baseDate->copy()->addMonths(2)->day(14)->format('Y-m-d'),
            'payee' => 'Valentines Day Gift',
            'amount' => -25000,
            'note' => 'Gift for partner',
            'category_id' => $categories['Gifts']->id,
        ]);

        Transaction::create([
            'account_id' => $checking->id,
            'team_id' => $team->id,
            'created_by' => $user->id,
            'date' => $baseDate->copy()->addMonths(4)->day(15)->format('Y-m-d'),
            'payee' => 'Birthday Gift',
            'amount' => -5000,
            'note' => 'Gift for friend',
            'category_id' => $categories['Gifts']->id,
        ]);
    }

    private function createCreditCardPayments($checking, $chaseCard, $amexCard, $baseDate, $user, $team, $categories): void
    {
        for ($i = 0; $i < 6; $i++) {
            $date = $baseDate->copy()->addMonths($i)->day(random_int(20, 28));

            Transaction::create([
                'account_id' => $chaseCard->id,
                'team_id' => $team->id,
                'created_by' => $user->id,
                'date' => $date->format('Y-m-d'),
                'payee' => 'Chase Card Payment',
                'amount' => random_int(150000, 250000),
                'note' => 'Monthly payment',
            ]);

            Transaction::create([
                'account_id' => $checking->id,
                'team_id' => $team->id,
                'created_by' => $user->id,
                'date' => $date->format('Y-m-d'),
                'payee' => 'Chase Card Payment',
                'amount' => -150000,
                'note' => 'Monthly payment',
                'category_id' => $categories['Transfer']->id,
            ]);

            $date->addDay();

            Transaction::create([
                'account_id' => $amexCard->id,
                'team_id' => $team->id,
                'created_by' => $user->id,
                'date' => $date->format('Y-m-d'),
                'payee' => 'Amex Card Payment',
                'amount' => random_int(50000, 100000),
                'note' => 'Monthly payment',
            ]);

            Transaction::create([
                'account_id' => $checking->id,
                'team_id' => $team->id,
                'created_by' => $user->id,
                'date' => $date->format('Y-m-d'),
                'payee' => 'Amex Card Payment',
                'amount' => -75000,
                'note' => 'Monthly payment',
                'category_id' => $categories['Transfer']->id,
            ]);
        }
    }

    private function createUncategorizedTransactions($account, $baseDate, $user, $team): void
    {
        for ($i = 0; $i < 15; $i++) {
            $date = $baseDate->copy()->addDays(random_int(0, 180));

            Transaction::create([
                'account_id' => $account->id,
                'team_id' => $team->id,
                'created_by' => $user->id,
                'date' => $date->format('Y-m-d'),
                'payee' => 'Unknown Merchant ' . random_int(1000, 9999),
                'amount' => random_int(1, 2) === 1 ? random_int(-5000, -500) : random_int(500, 5000),
                'note' => random_int(0, 1) ? 'Uncategorized transaction' : null,
            ]);
        }
    }

    private function createLargeTransactions($checking, $baseDate, $user, $team, $categories): void
    {
        $largeTransactions = [
            ['payee' => 'Home Improvement Store', 'amount' => -125000, 'note' => 'Kitchen renovation supplies', 'category' => 'Shopping'],
            ['payee' => 'Electronics Store', 'amount' => -250000, 'note' => 'New laptop', 'category' => 'Shopping'],
            ['payee' => 'Car Repair Shop', 'amount' => -85000, 'note' => 'Major repair', 'category' => 'Transportation'],
            ['payee' => 'Dentist Office', 'amount' => -35000, 'note' => 'Dental work', 'category' => 'Healthcare'],
            ['payee' => 'Travel Agency', 'amount' => -150000, 'note' => 'Upcoming vacation', 'category' => 'Travel'],
            ['payee' => 'Online Course Platform', 'amount' => -49900, 'note' => 'Professional development', 'category' => 'Education'],
            ['payee' => 'Furniture Store', 'amount' => -175000, 'note' => 'New sofa and coffee table', 'category' => 'Shopping'],
            ['payee' => 'Bonus Payment', 'amount' => 500000, 'note' => 'Year-end bonus', 'category' => 'Salary'],
        ];

        foreach ($largeTransactions as $transaction) {
            $date = $baseDate->copy()->addDays(random_int(30, 150));

            Transaction::create([
                'account_id' => $checking->id,
                'team_id' => $team->id,
                'created_by' => $user->id,
                'date' => $date->format('Y-m-d'),
                'payee' => $transaction['payee'],
                'amount' => $transaction['amount'],
                'note' => $transaction['note'],
                'category_id' => $categories[$transaction['category']]->id,
            ]);
        }
    }
}

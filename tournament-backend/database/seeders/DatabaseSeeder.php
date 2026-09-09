<?php
// Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
// SPDX-License-Identifier: LicenseRef-Proprietary

namespace Database\Seeders;

use App\Models\Achievement;
use App\Models\Banner;
use App\Models\BlogPost;
use App\Models\Coupon;
use App\Models\Friend;
use App\Models\Game;
use App\Models\GameMatch;
use App\Models\PlayerStat;
use App\Models\Product;
use App\Models\Sponsor;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\TournamentRegistration;
use App\Models\TournamentRule;
use App\Models\User;
use App\Services\BracketService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $devPassword = env('APP_ENV') === 'production' ? null : 'Arena2026!';
        $adminPassword = env('ADMIN_SEED_PASSWORD') ?: ($devPassword ?? Str::password(16));
        $playerPassword = env('PLAYER_SEED_PASSWORD') ?: ($devPassword ?? Str::password(16));

        $admin = $this->seedUser([
            'email' => 'admin@BeyondPlay.gg',
            'username' => 'ArenaAdmin',
            'password' => $adminPassword,
            'role' => 'admin',
            'country' => 'US',
            'bio' => 'BeyondPlay platform administrator.',
        ], $adminRole);

        $players = [];
        $playerDefs = [
            ['email' => 'tenz.fan@BeyondPlay.gg', 'username' => 'TenZFan_BD', 'country' => 'BD', 'gaming_id' => 'TenZFan#4821', 'bio' => 'Immortal-ranked Valorant player. VCT viewer and weekend competitor.'],
            ['email' => 'lynx@BeyondPlay.gg', 'username' => 'LynxGlitch', 'country' => 'IN', 'gaming_id' => 'LynxGlitch#1102', 'bio' => 'CS2 faceit level 8. Entry fragger for Nexus Gaming.'],
            ['email' => 'nova@BeyondPlay.gg', 'username' => 'NovaReign', 'country' => 'US', 'gaming_id' => 'NovaReign#NA1', 'bio' => 'LoL Emerald mid laner. Former college league player.'],
            ['email' => 'frost@BeyondPlay.gg', 'username' => 'FrostByte', 'country' => 'DE', 'gaming_id' => 'FrostByte#EUW', 'bio' => 'Dota 2 5k MMR support main. ESL Open veteran.'],
            ['email' => 'kairo@BeyondPlay.gg', 'username' => 'KairoX', 'country' => 'JP', 'gaming_id' => 'KairoX#JP', 'bio' => 'Fortnite competitive builder. Zero-build specialist.'],
            ['email' => 'mira@BeyondPlay.gg', 'username' => 'MiraShot', 'country' => 'BR', 'gaming_id' => 'MiraShot#BR', 'bio' => 'EA SPORTS FC pro clubs captain. Division 1 regular.'],
            ['email' => 'vex@BeyondPlay.gg', 'username' => 'VexAim', 'country' => 'PK', 'gaming_id' => 'VexAim#7721', 'bio' => 'Valorant Duelist. PUBG squad IGL on weekends.'],
            ['email' => 'sable@BeyondPlay.gg', 'username' => 'SableWolf', 'country' => 'GB', 'gaming_id' => 'SableWolf#UK', 'bio' => 'Multi-title grinder — CS2, Valorant, and Rocket League.'],
        ];

        foreach ($playerDefs as $def) {
            $players[] = $this->seedUser([
                'email' => $def['email'],
                'username' => $def['username'],
                'password' => $playerPassword,
                'role' => 'user',
                'country' => $def['country'],
                'gaming_id' => $def['gaming_id'],
                'bio' => $def['bio'],
            ], $userRole);
        }

        $games = $this->seedGames();
        $teams = $this->seedTeams($players);
        $tournaments = $this->seedTournaments($games, $admin, $players, $teams);
        $this->seedProducts();
        $this->seedBlog($admin);
        $this->seedSponsors();
        $this->seedBanners($admin);
        $this->seedCoupons();
        $this->seedAchievements();
        $this->seedSocial($players);
        $this->seedPlayerStats($players, $games);

        $this->seedBracketDemo($tournaments['cs2_showdown'], array_slice($players, 0, 8));

        $this->printCredentials($adminPassword, $playerPassword, $players);
    }

    protected function seedUser(array $data, Role $role): User
    {
        $user = User::updateOrCreate(
            ['email' => $data['email']],
            [
                'username' => $data['username'],
                'password' => Hash::make($data['password']),
                'role' => $data['role'],
                'status' => 'active',
                'country' => $data['country'] ?? null,
                'gaming_id' => $data['gaming_id'] ?? null,
                'bio' => $data['bio'] ?? null,
                'email_verified_at' => now(),
            ]
        );
        $user->syncRoles([$role]);

        return $user;
    }

    /**
     * @return array<string, Game>
     */
    protected function seedGames(): array
    {
        $defs = [
            [
                'name' => 'Valorant',
                'genre' => 'Tactical FPS',
                'description' => 'Free-to-play 5v5 character-based tactical shooter by Riot Games. The premier esports title with the VCT (Valorant Champions Tour) global circuit.',
            ],
            [
                'name' => 'Counter-Strike 2',
                'genre' => 'Tactical FPS',
                'description' => 'Valve\'s successor to CS:GO. 5v5 bomb-defusal gameplay with a thriving professional scene including Majors and ESL Pro League.',
            ],
            [
                'name' => 'League of Legends',
                'genre' => 'MOBA',
                'description' => 'Riot Games\' 5v5 MOBA with Worlds as its flagship annual championship — one of the most-watched esports events globally.',
            ],
            [
                'name' => 'Dota 2',
                'genre' => 'MOBA',
                'description' => 'Valve\'s deep strategic MOBA. The International regularly features multi-million dollar prize pools crowdfunded by the community.',
            ],
            [
                'name' => 'Fortnite',
                'genre' => 'Battle Royale',
                'description' => 'Epic Games\' build-and-shoot battle royale with FNCS (Fortnite Champion Series) serving as its official competitive circuit.',
            ],
            [
                'name' => 'EA SPORTS FC 25',
                'genre' => 'Sports',
                'description' => 'Electronic Arts\' football simulation (formerly FIFA). Features FUT Champions and pro clubs competitive modes.',
            ],
            [
                'name' => 'PUBG: BATTLEGROUNDS',
                'genre' => 'Battle Royale',
                'description' => 'Krafton\'s tactical battle royale that pioneered the genre. PUBG Global Championship is its top-tier annual event.',
            ],
            [
                'name' => 'Rocket League',
                'genre' => 'Sports',
                'description' => 'Psyonix/Epic\'s vehicular soccer game. RLCS (Rocket League Championship Series) runs regional leagues worldwide.',
            ],
        ];

        $models = [];
        foreach ($defs as $g) {
            $models[$g['name']] = Game::updateOrCreate(
                ['slug' => Str::slug($g['name'])],
                [
                    'name' => $g['name'],
                    'genre' => $g['genre'],
                    'description' => $g['description'],
                    'status' => 'active',
                ]
            );
        }

        return $models;
    }

    /**
     * @param  array<int, User>  $players
     * @return array<string, Team>
     */
    protected function seedTeams(array $players): array
    {
        $defs = [
            ['key' => 'nexus', 'name' => 'Nexus Gaming', 'captain' => 1, 'members' => [1, 2, 6]],
            ['key' => 'phoenix', 'name' => 'Phoenix Esports', 'captain' => 3, 'members' => [3, 4]],
            ['key' => 'storm', 'name' => 'Storm Legion', 'captain' => 5, 'members' => [5, 7]],
            ['key' => 'velvet', 'name' => 'Velvet Vipers', 'captain' => 6, 'members' => [6, 0]],
        ];

        $teams = [];
        foreach ($defs as $d) {
            $captain = $players[$d['captain']];
            $team = Team::updateOrCreate(
                ['slug' => Str::slug($d['name'])],
                [
                    'name' => $d['name'],
                    'captain_id' => $captain->id,
                    'status' => 'active',
                ]
            );
            foreach ($d['members'] as $idx) {
                $member = $players[$idx];
                $role = $member->id === $captain->id ? 'captain' : 'member';
                $team->members()->syncWithoutDetaching([
                    $member->id => ['role' => $role, 'joined_at' => now()->subDays(rand(10, 90))],
                ]);
            }
            $teams[$d['key']] = $team;
        }

        return $teams;
    }

    /**
     * @param  array<string, Game>  $games
     * @param  array<int, User>  $players
     * @param  array<string, Team>  $teams
     * @return array<string, Tournament>
     */
    protected function seedTournaments(array $games, User $admin, array $players, array $teams): array
    {
        $now = now();

        $defs = [
            'valorant_open' => [
                'name' => 'BeyondPlay Valorant Open Series — June 2026',
                'game' => 'Valorant',
                'format' => 'double_elimination',
                'status' => 'open',
                'prize_pool' => 5000,
                'entry_fee' => 0,
                'max_participants' => 32,
                'current_participants' => 12,
                'start_date' => $now->copy()->addWeeks(2),
                'registration_end' => $now->copy()->addWeek(),
            ],
            'cs2_showdown' => [
                'name' => 'CS2 Weekly Showdown #14',
                'game' => 'Counter-Strike 2',
                'format' => 'single_elimination',
                'status' => 'ongoing',
                'prize_pool' => 1500,
                'entry_fee' => 0,
                'max_participants' => 16,
                'current_participants' => 8,
                'start_date' => $now->copy()->subDay(),
                'registration_end' => $now->copy()->subDays(3),
            ],
            'lol_clash' => [
                'name' => 'League of Legends Community Clash',
                'game' => 'League of Legends',
                'format' => 'round_robin',
                'status' => 'open',
                'prize_pool' => 2000,
                'entry_fee' => 0,
                'max_participants' => 16,
                'current_participants' => 4,
                'start_date' => $now->copy()->addMonth(),
                'registration_end' => $now->copy()->addWeeks(3),
            ],
            'dota_cup' => [
                'name' => 'Dota 2 Open Cup — South Asia',
                'game' => 'Dota 2',
                'format' => 'single_elimination',
                'status' => 'open',
                'prize_pool' => 3000,
                'entry_fee' => 5.00,
                'max_participants' => 16,
                'current_participants' => 6,
                'start_date' => $now->copy()->addWeeks(4),
                'registration_end' => $now->copy()->addWeeks(3),
            ],
            'fc_proclubs' => [
                'name' => 'EA SPORTS FC 25 Pro Clubs Night',
                'game' => 'EA SPORTS FC 25',
                'format' => 'single_elimination',
                'status' => 'completed',
                'prize_pool' => 500,
                'entry_fee' => 0,
                'max_participants' => 8,
                'current_participants' => 8,
                'start_date' => $now->copy()->subWeeks(2),
                'registration_end' => $now->copy()->subWeeks(3),
                'end_date' => $now->copy()->subWeek(),
            ],
        ];

        $descriptions = [
            'valorant_open' => 'Open double-elimination bracket for Immortal+ teams and high-ranked solo players. Inspired by VCT Challengers open qualifiers — best-of-three from semifinals.',
            'cs2_showdown' => 'Weekly 1v1-style solo bracket on BeyondPlay. Faceit-style rules: MR12, knife round for side choice. Top 8 advance from Swiss-style seeding.',
            'lol_clash' => 'Round-robin group stage for 5v5 premade teams. Top 4 advance to playoffs. Summoner\'s Rift, draft pick, patch 14.x.',
            'dota_cup' => 'Single-elimination open for stacks and solo players. Captain\'s Mode, BO3 from quarterfinals. $5 entry fee supports the prize pool.',
            'fc_proclubs' => 'Completed 11v11 Pro Clubs tournament. Full-length matches, no golden goal — extra time then penalties.',
        ];

        $tournaments = [];
        foreach ($defs as $key => $t) {
            $tournament = Tournament::updateOrCreate(
                ['slug' => Str::slug($t['name'])],
                [
                    'game_id' => $games[$t['game']]->id,
                    'name' => $t['name'],
                    'description' => $descriptions[$key],
                    'entry_fee' => $t['entry_fee'],
                    'prize_pool' => $t['prize_pool'],
                    'max_participants' => $t['max_participants'],
                    'current_participants' => $t['current_participants'],
                    'format' => $t['format'],
                    'status' => $t['status'],
                    'registration_start' => $now->copy()->subMonth(),
                    'registration_end' => $t['registration_end'],
                    'start_date' => $t['start_date'],
                    'end_date' => $t['end_date'] ?? null,
                    'checkin_minutes_before' => 15,
                    'created_by' => $admin->id,
                ]
            );

            TournamentRule::updateOrCreate(
                ['tournament_id' => $tournament->id],
                $this->rulesFor($key)
            );

            $tournaments[$key] = $tournament;
        }

        $this->seedRegistrations($tournaments, $players, $teams);

        return $tournaments;
    }

    /**
     * @return array<string, string>
     */
    protected function rulesFor(string $key): array
    {
        return match ($key) {
            'valorant_open' => [
                'format_details' => 'Double-elimination, 5v5. BO1 through quarterfinals, BO3 semifinals and grand final. Standard competitive map pool (Ascent, Bind, Haven, Lotus, Sunset).',
                'schedule_info' => 'Check-in opens 15 minutes before each match. Schedule published 48 hours before event start. All times UTC.',
                'scoring_rules' => 'Match win advances. Map wins break series ties. Overtime follows official Valorant competitive rules.',
                'code_of_conduct' => 'No cheating, smurfing, or harassment. Riot Vanguard must be active. Stream sniping is prohibited.',
                'dispute_policy' => 'Submit disputes within 30 minutes with demo links or VOD timestamps. Admin decision is final within 24 hours.',
            ],
            'cs2_showdown' => [
                'format_details' => 'Single-elimination solo bracket (1v1 BeyondPlay maps). MR12, standard competitive settings, 128-tick server.',
                'schedule_info' => 'Round of 8 starts Saturday 18:00 UTC. Winners play every 45 minutes until grand final.',
                'scoring_rules' => 'First to 13 rounds wins. Overtime MR3 with $10k start.',
                'code_of_conduct' => 'VAC-ban free account required. No coaching during live matches.',
                'dispute_policy' => 'Open a ticket with match ID and Steam demo within 20 minutes.',
            ],
            default => [
                'format_details' => 'See tournament description for bracket type and match format.',
                'schedule_info' => 'Match times posted on the tournament page after registration closes.',
                'scoring_rules' => 'Winners advance per standard elimination rules.',
                'code_of_conduct' => 'Fair play, respect opponents, follow game ToS.',
                'dispute_policy' => 'Use the in-platform dispute form with evidence URLs.',
            ],
        };
    }

    /**
     * @param  array<string, Tournament>  $tournaments
     * @param  array<int, User>  $players
     * @param  array<string, Team>  $teams
     */
    protected function seedRegistrations(array $tournaments, array $players, array $teams): void
    {
        foreach (array_slice($players, 0, 6) as $p) {
            TournamentRegistration::updateOrCreate(
                ['tournament_id' => $tournaments['valorant_open']->id, 'user_id' => $p->id],
                ['team_id' => null, 'status' => 'confirmed', 'registered_at' => now()->subDays(rand(1, 10))]
            );
        }

        TournamentRegistration::updateOrCreate(
            ['tournament_id' => $tournaments['lol_clash']->id, 'user_id' => $players[2]->id],
            ['team_id' => $teams['phoenix']->id, 'status' => 'confirmed', 'registered_at' => now()->subDays(5)]
        );
        TournamentRegistration::updateOrCreate(
            ['tournament_id' => $tournaments['lol_clash']->id, 'user_id' => $players[3]->id],
            ['team_id' => $teams['phoenix']->id, 'status' => 'confirmed', 'registered_at' => now()->subDays(5)]
        );

        foreach (array_slice($players, 0, 4) as $p) {
            TournamentRegistration::updateOrCreate(
                ['tournament_id' => $tournaments['dota_cup']->id, 'user_id' => $p->id],
                ['team_id' => null, 'status' => 'confirmed', 'registered_at' => now()->subDays(rand(2, 8))]
            );
        }
    }

    protected function seedBracketDemo(Tournament $tournament, array $players): void
    {
        GameMatch::where('tournament_id', $tournament->id)->delete();

        foreach ($players as $p) {
            TournamentRegistration::updateOrCreate(
                ['tournament_id' => $tournament->id, 'user_id' => $p->id],
                ['team_id' => null, 'status' => 'confirmed', 'registered_at' => now()->subDays(5)]
            );
        }

        $tournament->update(['current_participants' => count($players)]);

        try {
            app(BracketService::class)->generateBracket($tournament);
        } catch (\Throwable $e) {
            if ($this->command) {
                $this->command->warn('Bracket seed skipped: '.$e->getMessage());
            }

            return;
        }

        $round1 = GameMatch::where('tournament_id', $tournament->id)->where('round', 1)->get();
        foreach ($round1->take(4) as $i => $match) {
            $match->update([
                'team1_score' => 13,
                'team2_score' => $i % 2 === 0 ? 9 : 11,
                'winner_id' => $match->team1_id,
                'status' => $i === 0 ? 'live' : 'completed',
                'stream_url' => $i === 0 ? 'https://www.youtube.com/embed/jfKfPfyJRdk' : null,
                'completed_at' => $i === 0 ? null : now()->subHours(6 - $i),
            ]);
        }
    }

    protected function seedProducts(): void
    {
        $items = [
            ['name' => 'Logitech G Pro X Superlight 2', 'price' => 159.00, 'sale_price' => 139.00, 'category' => 'Mice', 'description' => '60g wireless esports mouse. HERO 2 sensor, up to 95 hours battery. Used by pros in CS2 and Valorant.'],
            ['name' => 'Razer DeathAdder V3 Pro', 'price' => 149.99, 'sale_price' => null, 'category' => 'Mice', 'description' => 'Ergonomic wireless mouse with Focus Pro 30K sensor and 90-hour battery life.'],
            ['name' => 'SteelSeries Arctis Nova Pro Wireless', 'price' => 349.99, 'sale_price' => 299.99, 'category' => 'Audio', 'description' => 'Premium dual-battery wireless headset with active noise cancellation and Sonar software.'],
            ['name' => 'HyperX Cloud III Wireless', 'price' => 169.99, 'sale_price' => null, 'category' => 'Audio', 'description' => '120-hour battery wireless headset with DTS spatial audio and 10mm drivers.'],
            ['name' => 'Wooting 60HE+', 'price' => 174.99, 'sale_price' => null, 'category' => 'Keyboards', 'description' => 'Analog Hall-effect keyboard with rapid trigger — the standard for competitive Valorant and CS2.'],
            ['name' => 'Keychron Q1 Pro', 'price' => 199.00, 'sale_price' => 179.00, 'category' => 'Keyboards', 'description' => 'Gasket-mount wireless mechanical keyboard with hot-swap switches and aluminum body.'],
            ['name' => 'Secretlab Titan Evo 2022', 'price' => 549.00, 'sale_price' => null, 'category' => 'Furniture', 'description' => 'Ergonomic esports chair with 4-way lumbar support. Official chair partner of many top teams.'],
            ['name' => 'BeyondPlay Tournament Pass — Season 1', 'price' => 24.99, 'sale_price' => 14.99, 'type' => 'digital', 'stock' => 9999, 'category' => 'Digital', 'digital_file' => 'digital/tournament-pass.txt', 'description' => 'Digital pass: priority registration, exclusive Discord role, and 10% shop discount for one season.'],
        ];

        $images = [
            'assets/img/product/product_thumb_1_1.png',
            'assets/img/product/product_thumb_1_2.png',
            'assets/img/product/product_thumb_1_3.png',
            'assets/img/product/product_thumb_1_4.png',
            'assets/img/product/product_thumb_1_5.png',
            'assets/img/product/product_thumb_1_1.png',
            'assets/img/product/product_thumb_1_2.png',
            'assets/img/product/product_thumb_1_3.png',
        ];

        foreach ($items as $i => $p) {
            Product::updateOrCreate(
                ['slug' => Str::slug($p['name'])],
                [
                    'name' => $p['name'],
                    'description' => $p['description'],
                    'price' => $p['price'],
                    'sale_price' => $p['sale_price'],
                    'stock' => $p['stock'] ?? rand(12, 48),
                    'category' => $p['category'],
                    'type' => $p['type'] ?? 'physical',
                    'digital_file' => $p['digital_file'] ?? null,
                    'image' => $images[$i % count($images)],
                    'status' => 'active',
                ]
            );
        }

        Product::whereIn('slug', [
            'gaming-headphone', 'gaming-mouse', 'gaming-keyboard', 'gaming-chair', 'tournament-pass-dlc',
        ])->delete();
    }

    protected function seedBlog(User $admin): void
    {
        $posts = [
            [
                'title' => 'Valorant Champions Tour 2026: How Open Qualifiers Work',
                'category' => 'Tournaments',
                'excerpt' => 'A practical guide to VCT Challengers, open qualifiers, and how BeyondPlay mirrors the path from ranked to regional leagues.',
                'content' => '<p>The Valorant Champions Tour (VCT) is Riot Games\' official global circuit. Teams typically enter through Challengers leagues in their region, with open qualifiers offering a path for new rosters.</p><p>On BeyondPlay, our Valorant Open Series uses a similar double-elimination format so squads can experience professional-style brackets before committing to a full season.</p><p><strong>Key dates:</strong> VCT international events run throughout the year; check riotgames.com/valorant for the current schedule.</p>',
            ],
            [
                'title' => 'CS2 Major Championships: What Every Player Should Know',
                'category' => 'Guides',
                'excerpt' => 'Valve Majors, MR12 format, and how weekly online brackets build skills for LAN competition.',
                'content' => '<p>Counter-Strike 2 Majors are sponsored by Valve and feature the best teams worldwide. Matches use MR12 (first to 13 rounds) with a standardized competitive config.</p><p>BeyondPlay\'s CS2 Weekly Showdown uses the same round structure on practice servers — a low-stakes way to build clutch discipline.</p>',
            ],
            [
                'title' => 'Building a Tournament-Ready PC Setup in 2026',
                'category' => 'Hardware',
                'excerpt' => 'Monitors, peripherals, and network tips used by ESL and VCT players — without overspending.',
                'content' => '<p>Pros prioritize stable 240Hz+ displays, wired connections for mice, and headsets with clear positional audio. The Logitech G Pro line and Wooting analog keyboards dominate FPS leaderboards.</p><p>Shop BeyondPlay\'s curated peripherals — all items match what we see at ESL and Red Bull LAN events.</p>',
            ],
            [
                'title' => 'The International 2026 Prize Pool: How Dota 2 Crowdfunding Works',
                'category' => 'News',
                'excerpt' => 'Battle Pass contributions, compendium history, and why community-funded prize pools changed esports forever.',
                'content' => '<p>Dota 2\'s The International famously raised over $40 million in prize money through Battle Pass sales. A portion of each purchase feeds the championship pool.</p><p>BeyondPlay\'s Dota 2 Open Cup uses a modest $5 entry fee to seed prizes locally — inspired by the same community-funded spirit.</p>',
            ],
        ];

        foreach ($posts as $post) {
            BlogPost::updateOrCreate(
                ['slug' => Str::slug($post['title'])],
                [
                    'author_id' => $admin->id,
                    'title' => $post['title'],
                    'excerpt' => $post['excerpt'],
                    'content' => $post['content'],
                    'category' => $post['category'],
                    'status' => 'published',
                    'published_at' => now()->subDays(rand(3, 30)),
                    'cover_image' => 'assets/img/blog/blog_1_1.jpg',
                ]
            );
        }
    }

    protected function seedSponsors(): void
    {
        $sponsors = [
            ['name' => 'Logitech G', 'logo' => 'assets/img/logo.svg', 'website_url' => 'https://www.logitechg.com', 'tier' => 'title', 'sort_order' => 1],
            ['name' => 'Razer', 'logo' => 'assets/img/logo.svg', 'website_url' => 'https://www.razer.com', 'tier' => 'gold', 'sort_order' => 2],
            ['name' => 'Red Bull', 'logo' => 'assets/img/logo.svg', 'website_url' => 'https://www.redbull.com/gaming', 'tier' => 'gold', 'sort_order' => 3],
            ['name' => 'Intel Gaming', 'logo' => 'assets/img/logo.svg', 'website_url' => 'https://www.intel.com/gaming', 'tier' => 'silver', 'sort_order' => 4],
        ];

        foreach ($sponsors as $s) {
            Sponsor::updateOrCreate(
                ['name' => $s['name']],
                [
                    'logo' => $s['logo'],
                    'website_url' => $s['website_url'],
                    'tier' => $s['tier'],
                    'is_active' => true,
                    'sort_order' => $s['sort_order'],
                ]
            );
        }

        Sponsor::where('name', 'BeyondPlay Partner')->delete();
    }

    protected function seedBanners(User $admin): void
    {
        Banner::updateOrCreate(
            ['title' => 'Valorant Open Series — Registration Live'],
            [
                'message' => 'Double-elimination bracket, $5,000 prize pool. Register before slots fill.',
                'link' => 'tournament-details.html?slug=BeyondPlay-valorant-open-series-june-2026',
                'type' => 'info',
                'is_active' => true,
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addMonths(2),
                'created_by' => $admin->id,
            ]
        );

        Banner::updateOrCreate(
            ['title' => 'CS2 Weekly Showdown — Round of 8 Live'],
            [
                'message' => 'Watch bracket updates and live scores on BeyondPlay.',
                'link' => 'bracket.html?id=2',
                'type' => 'success',
                'is_active' => true,
                'starts_at' => now()->subHours(6),
                'ends_at' => now()->addWeek(),
                'created_by' => $admin->id,
            ]
        );

        Banner::where('title', 'BeyondPlay Winter Championship')->delete();
    }

    protected function seedCoupons(): void
    {
        Coupon::updateOrCreate(
            ['code' => 'WELCOME15'],
            [
                'type' => 'percentage',
                'value' => 15,
                'min_order_amount' => 25,
                'max_uses' => 500,
                'used_count' => 0,
                'expires_at' => now()->addMonths(6),
                'status' => 'active',
            ]
        );

        Coupon::updateOrCreate(
            ['code' => 'GEAR10'],
            [
                'type' => 'percentage',
                'value' => 10,
                'min_order_amount' => 50,
                'max_uses' => 200,
                'used_count' => 0,
                'expires_at' => now()->addYear(),
                'status' => 'active',
            ]
        );

        Coupon::where('code', 'ARENA10')->delete();
    }

    protected function seedAchievements(): void
    {
        $achievements = [
            ['name' => 'First Blood', 'description' => 'Win your first match on BeyondPlay.', 'icon' => 'fa-trophy', 'condition_type' => 'wins', 'condition_value' => 1],
            ['name' => 'Road to Pro', 'description' => 'Play 25 tournament matches.', 'icon' => 'fa-medal', 'condition_type' => 'matches_played', 'condition_value' => 25],
            ['name' => 'Squad Goals', 'description' => 'Add 5 friends on BeyondPlay.', 'icon' => 'fa-users', 'condition_type' => 'friends_count', 'condition_value' => 5],
            ['name' => 'Champion', 'description' => 'Win a tournament.', 'icon' => 'fa-crown', 'condition_type' => 'tournament_wins', 'condition_value' => 1],
        ];

        foreach ($achievements as $a) {
            Achievement::updateOrCreate(
                ['name' => $a['name']],
                [
                    'description' => $a['description'],
                    'icon' => $a['icon'],
                    'condition_type' => $a['condition_type'],
                    'condition_value' => $a['condition_value'],
                ]
            );
        }
    }

    /**
     * @param  array<int, User>  $players
     */
    protected function seedSocial(array $players): void
    {
        $pairs = [[0, 1], [0, 2], [1, 3], [2, 4], [3, 5], [4, 6], [5, 7]];

        foreach ($pairs as [$a, $b]) {
            $u1 = $players[$a]->id;
            $u2 = $players[$b]->id;
            Friend::updateOrCreate(
                [
                    'requester_id' => min($u1, $u2),
                    'receiver_id' => max($u1, $u2),
                ],
                ['status' => 'accepted']
            );
        }

        Friend::updateOrCreate(
            ['requester_id' => $players[6]->id, 'receiver_id' => $players[0]->id],
            ['status' => 'pending']
        );
    }

    /**
     * @param  array<int, User>  $players
     * @param  array<string, Game>  $games
     */
    protected function seedPlayerStats(array $players, array $games): void
    {
        $gameKeys = ['Valorant', 'Counter-Strike 2', 'League of Legends', 'Dota 2'];
        foreach ($players as $i => $player) {
            foreach (array_slice($gameKeys, 0, 2 + ($i % 3)) as $gName) {
                $game = $games[$gName];
                $played = rand(12, 80);
                $wins = (int) round($played * (0.35 + ($i % 5) * 0.05));
                PlayerStat::updateOrCreate(
                    ['user_id' => $player->id, 'game_id' => $game->id],
                    [
                        'matches_played' => $played,
                        'wins' => $wins,
                        'losses' => $played - $wins,
                        'kills' => rand(80, 400),
                        'deaths' => rand(60, 350),
                        'total_score' => rand(5000, 50000),
                        'tournament_wins' => $i < 3 ? rand(0, 2) : 0,
                        'win_streak' => rand(0, 5),
                        'best_streak' => rand(3, 12),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }

    /**
     * @param  array<int, User>  $players
     */
    protected function printCredentials(string $adminPassword, string $playerPassword, array $players): void
    {
        if (! $this->command) {
            return;
        }

        $this->command->newLine();
        $this->command->info('═══ BeyondPlay seed complete — demo accounts ═══');
        $this->command->line('Admin:  admin@BeyondPlay.gg  /  '.$adminPassword);
        $this->command->line('Player: '.$players[0]->email.'  /  '.$playerPassword);
        $this->command->line('(All demo players share the same password)');
        $this->command->newLine();
        $this->command->line('Demo players:');
        foreach ($players as $p) {
            $this->command->line('  • '.$p->username.' <'.$p->email.'>');
        }
        if (App::environment('production')) {
            $this->command->error('Production: rotate all seed passwords before go-live.');
        }
    }
}

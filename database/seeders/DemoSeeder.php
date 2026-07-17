<?php

namespace Database\Seeders;

use App\Enums\UpkeepKind;
use App\Models\Home;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Recreates the design prototype's sample inventory for the first home.
 * Dimensions are millimeters, money is cents, dates are relative to today
 * so the demo always shows live overdue/soon/upcoming states.
 */
class DemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $home = Home::firstOrFail();
        $upkeeper = User::firstOrFail();

        $cats = [];
        foreach ([
            'tools' => ['Tools', 'wrench', '#4f74e3', null],
            'power' => ['Power tools', 'drill', '#4f74e3', 'tools'],
            'hand' => ['Hand tools', 'wrench', '#4f74e3', 'tools'],
            'electronics' => ['Electronics', 'bolt', '#1f9d8f', null],
            'kitchen' => ['Kitchen', 'utensil', '#df8f3c', null],
            'outdoor' => ['Outdoor', 'globe', '#4e9b54', null],
            'documents' => ['Documents', 'doc', '#7c8597', null],
            'apparel' => ['Apparel', 'shirt', '#a866c8', null],
        ] as $key => [$label, $glyph, $color, $parent]) {
            $cats[$key] = $home->categories()->create([
                'label' => $label,
                'glyph' => $glyph,
                'color' => $color,
                'parent_id' => $parent === null ? null : $cats[$parent]->id,
            ]);
        }

        $tags = [];
        foreach ([
            'valuable' => '#8a5cc0',
            'fragile' => '#c0564a',
            'warranty' => '#d99a2b',
            'seasonal' => '#4e9b54',
            'loaner' => '#4f74e3',
        ] as $label => $color) {
            $tags[$label] = $home->tags()->create(['label' => $label, 'color' => $color]);
        }

        $places = [];
        foreach ([
            'garage' => ['Garage', null, 'home', 'Main two-car garage', [6000, 3000, 7000]],
            'shelfb' => ['Shelf B', 'garage', 'box', 'Metal shelving by the door', [900, 400, 1800]],
            'workbench' => ['Workbench', 'garage', 'wrench', 'Under the pegboard', [1800, 900, 800]],
            'gfloor' => ['Floor', 'garage', 'box', null, [4000, 2000, 2500]],
            'kitchen' => ['Kitchen', null, 'home', 'Ground-floor kitchen', [4000, 3000, 4000]],
            'cabinets' => ['Cabinets', 'kitchen', 'box', null, [1200, 450, 2000]],
            'pantry' => ['Pantry', 'kitchen', 'box', null, [800, 500, 1800]],
            'office' => ['Office', null, 'home', 'Upstairs home office', [3500, 3000, 3000]],
            'desk' => ['Desk', 'office', 'box', null, [1400, 750, 700]],
            'drawer1' => ['Drawer 1', 'office', 'box', null, null],
            'closet' => ['Closet', null, 'home', null, [1500, 2400, 700]],
            'bin2' => ['Bin 2', 'closet', 'box', null, [600, 420, 400]],
            'basement' => ['Basement', null, 'home', 'Unfinished storage area', [5000, 2500, 6000]],
            'pegboard' => ['Pegboard', 'basement', 'wrench', null, [1200, 80, 900]],
        ] as $key => [$label, $parent, $glyph, $description, $dim]) {
            $places[$key] = $home->places()->create([
                'label' => $label,
                'parent_id' => $parent === null ? null : $places[$parent]->id,
                'glyph' => $glyph,
                'description' => $description,
                'dim' => $dim,
            ]);
        }

        $items = [];
        foreach ([
            'drill' => ['Cordless drill', 'power', 'shelfb', 12000, 1, [250, 220, 90], ['valuable'], 'DeWalt 20V'],
            'tires' => ['Winter tires ×4', 'outdoor', 'gfloor', 64000, 4, [630, 630, 210], ['seasonal'], '215/55 R17'],
            'mixer' => ['KitchenAid mixer', 'kitchen', 'cabinets', 38000, 1, [350, 400, 220], ['valuable', 'fragile'], 'Artisan, empire red'],
            'passport' => ['Passport', 'documents', 'drawer1', null, 1, [130, 10, 90], ['valuable'], 'Exp. 2029'],
            'jacket' => ['Ski jacket', 'apparel', 'bin2', 21000, 1, [400, 120, 300], ['seasonal'], "Arc'teryx, M"],
            'router' => ['Wi-Fi router', 'electronics', 'desk', 9000, 1, [140, 50, 140], ['warranty'], 'Eero Pro 6'],
            'compressor' => ['Air compressor', 'power', 'workbench', 26000, 1, [500, 550, 280], [], null],
            'bits' => ['Drill bit set', 'hand', 'shelfb', 3500, 1, [220, 50, 120], [], null],
            'tent' => ['Camping tent', 'outdoor', 'basement', 30000, 1, [600, 220, 220], ['seasonal'], '4-person'],
            'espresso' => ['Espresso machine', 'kitchen', 'cabinets', 54000, 1, [330, 400, 300], ['valuable', 'warranty'], 'Breville'],
            'extinguisher' => ['Fire extinguisher', 'tools', 'kitchen', 4500, 2, [120, 450, 120], [], null],
            'games' => ['Board games ×3', 'apparel', 'bin2', 8000, 3, [270, 150, 270], [], null],
        ] as $key => [$name, $cat, $place, $value, $qty, $dim, $itemTags, $note]) {
            $items[$key] = $home->items()->create([
                'name' => $name,
                'category_id' => $cats[$cat]->id,
                'place_id' => $places[$place]->id,
                'value' => $value,
                'qty' => $qty,
                'dim' => $dim,
                'note' => $note,
            ]);

            $items[$key]->tags()->attach(array_map(fn (string $tag) => $tags[$tag]->id, $itemTags));
        }

        foreach ([
            ['drill', 'Marco', 10, -4, true],
            ['tent', 'Aunt Rosa', 6, 4, true],
            ['games', 'Priya', 15, null, false],
        ] as [$item, $person, $outDaysAgo, $dueInDays, $remind]) {
            $home->lends()->create([
                'item_id' => $items[$item]->id,
                'person' => $person,
                'out_date' => today()->subDays($outDaysAgo),
                'due_date' => $dueInDays === null ? null : today()->addDays($dueInDays),
                'remind' => $remind,
            ]);
        }

        $furnaceFilter = $home->upkeepTasks()->create([
            'subject' => 'Furnace',
            'kind' => UpkeepKind::Maint,
            'task' => 'Replace air filter',
            'due_date' => today()->subDays(2),
            'every' => 'P3M',
        ]);

        $home->upkeepTasks()->create([
            'item_id' => $items['extinguisher']->id,
            'subject' => 'Fire extinguisher',
            'kind' => UpkeepKind::Expiry,
            'task' => 'Inspection expires',
            'due_date' => today()->addDays(5),
        ]);

        $home->upkeepTasks()->create([
            'subject' => 'Lawnmower',
            'kind' => UpkeepKind::Maint,
            'task' => 'Service & sharpen blade',
            'due_date' => today()->addDays(16),
            'every' => 'P1Y',
        ]);

        $home->upkeepTasks()->create([
            'item_id' => $items['router']->id,
            'subject' => 'Wi-Fi router',
            'kind' => UpkeepKind::Maint,
            'task' => 'Restart & update',
            'due_date' => today()->addDays(23),
            'every' => 'P1M',
        ]);

        foreach ([
            [$furnaceFilter->id, 'Replace air filter', 94],
            [$furnaceFilter->id, 'Replace air filter', 186],
            [null, 'Cleaned gutters', 14],
            [null, 'Tested smoke alarms', 27],
        ] as [$taskId, $task, $daysAgo]) {
            $home->upkeepLogs()->create([
                'upkeep_task_id' => $taskId,
                'user_id' => $upkeeper->id,
                'task' => $task,
                'completed_on' => today()->subDays($daysAgo),
            ]);
        }
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GigateamSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Company Settings ─────────────────────────────────────────────
        $settings = [
            // Company identity
            ['key' => 'company_name',      'value' => 'Gigateam Solutions Limited', 'group' => 'company'],
            ['key' => 'company_tagline',   'value' => 'Secured & Connected',         'group' => 'company'],
            ['key' => 'company_kra_pin',   'value' => 'P051892936Q',                 'group' => 'company'],
            ['key' => 'company_vat_rate',  'value' => '16',                          'group' => 'company'],
            // Address
            ['key' => 'company_address1',  'value' => 'White Angle House',           'group' => 'company'],
            ['key' => 'company_address2',  'value' => '1st Floor – Suite 62',        'group' => 'company'],
            ['key' => 'company_po_box',    'value' => 'P.O. Box 47271-00100',        'group' => 'company'],
            ['key' => 'company_city',      'value' => 'Nairobi, Kenya',              'group' => 'company'],
            // Contacts
            ['key' => 'company_phone1',    'value' => '+254 111292948',              'group' => 'company'],
            ['key' => 'company_phone2',    'value' => '+254 718811661',              'group' => 'company'],
            ['key' => 'company_email1',    'value' => 'sales@gigateamltd.com',       'group' => 'company'],
            ['key' => 'company_email2',    'value' => 'gigateamsolutions@gmail.com', 'group' => 'company'],
            ['key' => 'company_website',   'value' => 'www.gigateamsolutions.co.ke', 'group' => 'company'],
            // Document defaults
            ['key' => 'document_footer',   'value' => 'Accounts are due on demand.','group' => 'documents'],
            ['key' => 'quotation_terms',   'value' => 'This quotation is valid for 30 days from the date of issue. Prices are subject to change without prior notice. 50% deposit required before commencement of work.', 'group' => 'documents'],
            ['key' => 'invoice_terms',     'value' => 'Accounts are due on demand. All goods remain the property of Gigateam Solutions Limited until full payment is received.', 'group' => 'documents'],
            ['key' => 'low_stock_alert',   'value' => '5',                           'group' => 'inventory'],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->insertOrIgnore($setting + ['created_at' => now(), 'updated_at' => now()]);
        }

        // ── 2. Document Sequences ────────────────────────────────────────────
        $sequences = [
            ['type' => 'quotation',      'prefix' => 'QT',  'last_number' => 0,     'padding' => 4],
            ['type' => 'sale',           'prefix' => 'SAL', 'last_number' => 0,     'padding' => 4],
            ['type' => 'invoice',        'prefix' => 'INV', 'last_number' => 23477, 'padding' => 0], // continues from 23477
            ['type' => 'delivery_note',  'prefix' => 'DN',  'last_number' => 0,     'padding' => 4],
            ['type' => 'job_card',       'prefix' => 'JOB', 'last_number' => 0,     'padding' => 4],
            ['type' => 'purchase_order', 'prefix' => 'PO',  'last_number' => 0,     'padding' => 4],
        ];

        foreach ($sequences as $seq) {
            DB::table('document_sequences')->insertOrIgnore($seq + ['created_at' => now(), 'updated_at' => now()]);
        }

        // ── 3. Product Categories ────────────────────────────────────────────
        $categories = [
            ['name' => 'CCTV Cameras',        'slug' => 'cctv-cameras'],
            ['name' => 'DVR / NVR',            'slug' => 'dvr-nvr'],
            ['name' => 'Cables & Accessories', 'slug' => 'cables-accessories'],
            ['name' => 'Networking',           'slug' => 'networking'],
            ['name' => 'Biometric Devices',    'slug' => 'biometric'],
            ['name' => 'Electric Fencing',     'slug' => 'electric-fencing'],
            ['name' => 'Alarm Systems',        'slug' => 'alarm-systems'],
            ['name' => 'Access Control',       'slug' => 'access-control'],
            ['name' => 'UPS & Power',          'slug' => 'ups-power'],
            ['name' => 'Installation Labour',  'slug' => 'installation-labour'],
        ];

        foreach ($categories as &$cat) {
            $cat['is_active'] = true;
            $cat['created_at'] = now();
            $cat['updated_at'] = now();
        }
        DB::table('product_categories')->insertOrIgnore($categories);

        // Helper to get category ID
        $catId = fn(string $slug) => DB::table('product_categories')->where('slug', $slug)->value('id');

        // ── 4. Products ──────────────────────────────────────────────────────
        $products = [
            // CCTV Cameras
            ['category_id' => $catId('cctv-cameras'), 'name' => 'Hikvision 2MP Bullet Camera',       'sku' => 'CAM-HIK-2MP-BL', 'brand' => 'Hikvision', 'unit' => 'pcs',  'cost_price' => 2800,  'selling_price' => 4500,  'installation_price' => 800,  'stock_quantity' => 20],
            ['category_id' => $catId('cctv-cameras'), 'name' => 'Hikvision 4MP Dome Camera',         'sku' => 'CAM-HIK-4MP-DM', 'brand' => 'Hikvision', 'unit' => 'pcs',  'cost_price' => 4200,  'selling_price' => 6500,  'installation_price' => 800,  'stock_quantity' => 15],
            ['category_id' => $catId('cctv-cameras'), 'name' => '4MP Smart Hybrid Light Camera',     'sku' => 'CAM-HIK-4MP-SH', 'brand' => 'Hikvision', 'unit' => 'pcs',  'cost_price' => 4500,  'selling_price' => 6500,  'installation_price' => 800,  'stock_quantity' => 12],
            ['category_id' => $catId('cctv-cameras'), 'name' => 'Dahua 2MP PTZ Camera',              'sku' => 'CAM-DAH-PTZ-2M', 'brand' => 'Dahua',     'unit' => 'pcs',  'cost_price' => 12000, 'selling_price' => 18000, 'installation_price' => 2000, 'stock_quantity' => 5],
            ['category_id' => $catId('cctv-cameras'), 'name' => 'Hikvision 8MP 4K Bullet Camera',   'sku' => 'CAM-HIK-8MP-BL', 'brand' => 'Hikvision', 'unit' => 'pcs',  'cost_price' => 7500,  'selling_price' => 12000, 'installation_price' => 1000, 'stock_quantity' => 8],
            ['category_id' => $catId('cctv-cameras'), 'name' => 'Hikvision Fisheye 360 Camera',     'sku' => 'CAM-HIK-FE-360', 'brand' => 'Hikvision', 'unit' => 'pcs',  'cost_price' => 6500,  'selling_price' => 10000, 'installation_price' => 1200, 'stock_quantity' => 6],

            // DVR/NVR
            ['category_id' => $catId('dvr-nvr'), 'name' => 'Hikvision 4-Channel DVR',      'sku' => 'DVR-HIK-04CH', 'brand' => 'Hikvision', 'unit' => 'pcs', 'cost_price' => 5500,  'selling_price' => 8500,  'installation_price' => 500, 'stock_quantity' => 10],
            ['category_id' => $catId('dvr-nvr'), 'name' => 'Hikvision 8-Channel DVR',      'sku' => 'DVR-HIK-08CH', 'brand' => 'Hikvision', 'unit' => 'pcs', 'cost_price' => 7500,  'selling_price' => 11500, 'installation_price' => 500, 'stock_quantity' => 8],
            ['category_id' => $catId('dvr-nvr'), 'name' => 'Hikvision 16-Channel NVR',     'sku' => 'NVR-HIK-16CH', 'brand' => 'Hikvision', 'unit' => 'pcs', 'cost_price' => 14000, 'selling_price' => 17500, 'installation_price' => 500, 'stock_quantity' => 5],
            ['category_id' => $catId('dvr-nvr'), 'name' => 'Dahua 32-Channel NVR',         'sku' => 'NVR-DAH-32CH', 'brand' => 'Dahua',     'unit' => 'pcs', 'cost_price' => 25000, 'selling_price' => 38000, 'installation_price' => 500, 'stock_quantity' => 3],
            ['category_id' => $catId('dvr-nvr'), 'name' => '1TB Surveillance Hard Disk',   'sku' => 'HDD-SURV-1TB',  'brand' => 'Seagate',   'unit' => 'pcs', 'cost_price' => 5000,  'selling_price' => 7500,  'installation_price' => 0,   'stock_quantity' => 15],
            ['category_id' => $catId('dvr-nvr'), 'name' => '2TB Surveillance Hard Disk',   'sku' => 'HDD-SURV-2TB',  'brand' => 'Seagate',   'unit' => 'pcs', 'cost_price' => 8500,  'selling_price' => 12000, 'installation_price' => 0,   'stock_quantity' => 10],
            ['category_id' => $catId('dvr-nvr'), 'name' => '4TB Surveillance Hard Disk',   'sku' => 'HDD-SURV-4TB',  'brand' => 'WD Purple', 'unit' => 'pcs', 'cost_price' => 12500, 'selling_price' => 18000, 'installation_price' => 0,   'stock_quantity' => 8],
            ['category_id' => $catId('dvr-nvr'), 'name' => '6TB Surveillance Hard Disk',   'sku' => 'HDD-SURV-6TB',  'brand' => 'WD Purple', 'unit' => 'pcs', 'cost_price' => 15000, 'selling_price' => 22000, 'installation_price' => 0,   'stock_quantity' => 6],

            // Cables & Accessories
            ['category_id' => $catId('cables-accessories'), 'name' => 'CAT6 Pure Copper Cable (305m)',  'sku' => 'CBL-CAT6-305',  'brand' => 'Generic', 'unit' => 'box',  'cost_price' => 10000, 'selling_price' => 13500, 'installation_price' => 0, 'stock_quantity' => 20],
            ['category_id' => $catId('cables-accessories'), 'name' => 'RG59 Coaxial Cable (100m)',       'sku' => 'CBL-RG59-100',  'brand' => 'Generic', 'unit' => 'roll', 'cost_price' => 1200,  'selling_price' => 1800,  'installation_price' => 0, 'stock_quantity' => 30],
            ['category_id' => $catId('cables-accessories'), 'name' => 'BNC Connectors (bag of 100)',     'sku' => 'ACC-BNC-100',   'brand' => 'Generic', 'unit' => 'bag',  'cost_price' => 350,   'selling_price' => 600,   'installation_price' => 0, 'stock_quantity' => 50],
            ['category_id' => $catId('cables-accessories'), 'name' => 'RJ45 Connectors (bag of 100)',    'sku' => 'ACC-RJ45-100',  'brand' => 'Generic', 'unit' => 'bag',  'cost_price' => 250,   'selling_price' => 450,   'installation_price' => 0, 'stock_quantity' => 60],
            ['category_id' => $catId('cables-accessories'), 'name' => 'Adapter Box / Junction Box',      'sku' => 'ACC-ADAPTER',   'brand' => 'Generic', 'unit' => 'pcs', 'cost_price' => 80,    'selling_price' => 150,   'installation_price' => 0, 'stock_quantity' => 200],
            ['category_id' => $catId('cables-accessories'), 'name' => '4U Wall Cabinet',                 'sku' => 'ACC-CAB-4U',    'brand' => 'Generic', 'unit' => 'pcs', 'cost_price' => 4500,  'selling_price' => 6500,  'installation_price' => 0, 'stock_quantity' => 10],
            ['category_id' => $catId('cables-accessories'), 'name' => 'Cable Trunking 40x20 (2m)',        'sku' => 'ACC-TRUNK-40',  'brand' => 'Generic', 'unit' => 'pcs', 'cost_price' => 120,   'selling_price' => 200,   'installation_price' => 0, 'stock_quantity' => 100],
            ['category_id' => $catId('cables-accessories'), 'name' => 'Installation Accessories Kit',    'sku' => 'ACC-INST-KIT',  'brand' => 'Generic', 'unit' => 'lot', 'cost_price' => 3000,  'selling_price' => 5000,  'installation_price' => 0, 'stock_quantity' => 20],

            // Networking
            ['category_id' => $catId('networking'), 'name' => '8-Port POE Switch',          'sku' => 'NET-POE-08',  'brand' => 'Generic', 'unit' => 'pcs', 'cost_price' => 4500,  'selling_price' => 7000,  'installation_price' => 0, 'stock_quantity' => 10],
            ['category_id' => $catId('networking'), 'name' => '16-Port Fast Ethernet POE Switch', 'sku' => 'NET-POE-16', 'brand' => 'Generic', 'unit' => 'pcs', 'cost_price' => 12000, 'selling_price' => 17000, 'installation_price' => 0, 'stock_quantity' => 8],
            ['category_id' => $catId('networking'), 'name' => '24-Port POE Switch',          'sku' => 'NET-POE-24',  'brand' => 'Generic', 'unit' => 'pcs', 'cost_price' => 18000, 'selling_price' => 26000, 'installation_price' => 0, 'stock_quantity' => 5],
            ['category_id' => $catId('networking'), 'name' => 'Wireless Access Point',       'sku' => 'NET-WAP-01',  'brand' => 'Ubiquiti', 'unit' => 'pcs', 'cost_price' => 6500,  'selling_price' => 10000, 'installation_price' => 800, 'stock_quantity' => 8],

            // Biometric
            ['category_id' => $catId('biometric'), 'name' => 'ZKTeco Fingerprint Time Attendance K40', 'sku' => 'BIO-ZKT-K40',   'brand' => 'ZKTeco',  'unit' => 'pcs', 'cost_price' => 5500,  'selling_price' => 8500,  'installation_price' => 1000, 'stock_quantity' => 8],
            ['category_id' => $catId('biometric'), 'name' => 'ZKTeco Face Recognition F22',            'sku' => 'BIO-ZKT-F22',   'brand' => 'ZKTeco',  'unit' => 'pcs', 'cost_price' => 18000, 'selling_price' => 28000, 'installation_price' => 1500, 'stock_quantity' => 4],
            ['category_id' => $catId('biometric'), 'name' => 'Suprema Biometric Reader',               'sku' => 'BIO-SUP-R1',    'brand' => 'Suprema', 'unit' => 'pcs', 'cost_price' => 32000, 'selling_price' => 50000, 'installation_price' => 2000, 'stock_quantity' => 3],

            // Electric Fencing
            ['category_id' => $catId('electric-fencing'), 'name' => 'Nemtek 8J Energiser',           'sku' => 'EF-NEM-8J',   'brand' => 'Nemtek',  'unit' => 'pcs', 'cost_price' => 12000, 'selling_price' => 18000, 'installation_price' => 3000, 'stock_quantity' => 5],
            ['category_id' => $catId('electric-fencing'), 'name' => 'Nemtek 16J Energiser',           'sku' => 'EF-NEM-16J',  'brand' => 'Nemtek',  'unit' => 'pcs', 'cost_price' => 20000, 'selling_price' => 30000, 'installation_price' => 3000, 'stock_quantity' => 3],
            ['category_id' => $catId('electric-fencing'), 'name' => 'High Tensile Fencing Wire (kg)', 'sku' => 'EF-WIRE-1KG', 'brand' => 'Generic', 'unit' => 'kg',  'cost_price' => 350,   'selling_price' => 600,   'installation_price' => 0,    'stock_quantity' => 200],
            ['category_id' => $catId('electric-fencing'), 'name' => 'Porcelain Insulators (50pcs)',   'sku' => 'EF-INS-50PK', 'brand' => 'Generic', 'unit' => 'pcs', 'cost_price' => 400,   'selling_price' => 700,   'installation_price' => 0,    'stock_quantity' => 50],
            ['category_id' => $catId('electric-fencing'), 'name' => 'Warning Signs (10pcs)',          'sku' => 'EF-SIGN-10PK','brand' => 'Generic', 'unit' => 'pcs', 'cost_price' => 500,   'selling_price' => 900,   'installation_price' => 0,    'stock_quantity' => 100],

            // Alarm Systems
            ['category_id' => $catId('alarm-systems'), 'name' => 'DSC 8-Zone Alarm Panel',    'sku' => 'ALM-DSC-8Z',  'brand' => 'DSC',    'unit' => 'pcs', 'cost_price' => 5500,  'selling_price' => 8500,  'installation_price' => 1500, 'stock_quantity' => 8],
            ['category_id' => $catId('alarm-systems'), 'name' => 'Optex PIR Motion Sensor',   'sku' => 'ALM-OPT-PIR', 'brand' => 'Optex', 'unit' => 'pcs', 'cost_price' => 1200,  'selling_price' => 2000,  'installation_price' => 300,  'stock_quantity' => 30],
            ['category_id' => $catId('alarm-systems'), 'name' => 'Outdoor Siren & Strobe',    'sku' => 'ALM-SIR-OUT', 'brand' => 'Generic','unit' => 'pcs', 'cost_price' => 800,   'selling_price' => 1500,  'installation_price' => 300,  'stock_quantity' => 20],
            ['category_id' => $catId('alarm-systems'), 'name' => 'Magnetic Door Contact',     'sku' => 'ALM-MAG-DC',  'brand' => 'Generic','unit' => 'pcs', 'cost_price' => 250,   'selling_price' => 450,   'installation_price' => 0,    'stock_quantity' => 50],
            ['category_id' => $catId('alarm-systems'), 'name' => 'Keypad for Alarm Panel',    'sku' => 'ALM-KPD-01',  'brand' => 'DSC',    'unit' => 'pcs', 'cost_price' => 2500,  'selling_price' => 4000,  'installation_price' => 300,  'stock_quantity' => 10],

            // Access Control
            ['category_id' => $catId('access-control'), 'name' => '600lb Magnetic Lock',          'sku' => 'ACS-MAG-600', 'brand' => 'Generic', 'unit' => 'pcs', 'cost_price' => 3500,  'selling_price' => 5500,  'installation_price' => 1000, 'stock_quantity' => 10],
            ['category_id' => $catId('access-control'), 'name' => 'RFID Card Reader',             'sku' => 'ACS-RFID-01', 'brand' => 'Generic', 'unit' => 'pcs', 'cost_price' => 1800,  'selling_price' => 3000,  'installation_price' => 500,  'stock_quantity' => 15],
            ['category_id' => $catId('access-control'), 'name' => 'Video Intercom System',        'sku' => 'ACS-VID-INT', 'brand' => 'Hikvision','unit' => 'pcs', 'cost_price' => 8500,  'selling_price' => 13500, 'installation_price' => 2000, 'stock_quantity' => 5],
            ['category_id' => $catId('access-control'), 'name' => 'Proximity Cards (pack of 10)', 'sku' => 'ACS-CARD-10', 'brand' => 'Generic', 'unit' => 'pcs', 'cost_price' => 500,   'selling_price' => 900,   'installation_price' => 0,    'stock_quantity' => 100],

            // UPS & Power
            ['category_id' => $catId('ups-power'), 'name' => '1KVA UPS',          'sku' => 'UPS-1KVA',  'brand' => 'APC',     'unit' => 'pcs', 'cost_price' => 8000,  'selling_price' => 12000, 'installation_price' => 0, 'stock_quantity' => 5],
            ['category_id' => $catId('ups-power'), 'name' => 'Power Surge Protector', 'sku' => 'PWR-SURGE', 'brand' => 'Generic', 'unit' => 'pcs', 'cost_price' => 800,   'selling_price' => 1500,  'installation_price' => 0, 'stock_quantity' => 20],

            // Installation Labour (service items — no stock)
            ['category_id' => $catId('installation-labour'), 'name' => 'Camera Installation (per camera)',          'sku' => 'SVC-CAM-INST',  'brand' => '', 'unit' => 'pcs',  'cost_price' => 0, 'selling_price' => 800,   'installation_price' => 0, 'stock_quantity' => 0, 'is_service' => true],
            ['category_id' => $catId('installation-labour'), 'name' => 'Network Cabling (per point)',               'sku' => 'SVC-NET-PT',    'brand' => '', 'unit' => 'pts',  'cost_price' => 0, 'selling_price' => 500,   'installation_price' => 0, 'stock_quantity' => 0, 'is_service' => true],
            ['category_id' => $catId('installation-labour'), 'name' => 'Electric Fence Installation (per metre)',   'sku' => 'SVC-EF-MTR',    'brand' => '', 'unit' => 'm',    'cost_price' => 0, 'selling_price' => 350,   'installation_price' => 0, 'stock_quantity' => 0, 'is_service' => true],
            ['category_id' => $catId('installation-labour'), 'name' => 'Labour, Transport & Commissioning',         'sku' => 'SVC-LAB-TRANS', 'brand' => '', 'unit' => 'job',  'cost_price' => 0, 'selling_price' => 35000, 'installation_price' => 0, 'stock_quantity' => 0, 'is_service' => true],
            ['category_id' => $catId('installation-labour'), 'name' => 'Site Survey & Consultation',                'sku' => 'SVC-SURVEY',    'brand' => '', 'unit' => 'job',  'cost_price' => 0, 'selling_price' => 3000,  'installation_price' => 0, 'stock_quantity' => 0, 'is_service' => true],
            ['category_id' => $catId('installation-labour'), 'name' => 'Annual Maintenance Contract',               'sku' => 'SVC-AMC',       'brand' => '', 'unit' => 'year', 'cost_price' => 0, 'selling_price' => 15000, 'installation_price' => 0, 'stock_quantity' => 0, 'is_service' => true],
        ];

        foreach ($products as &$p) {
            $p['low_stock_threshold'] = 3;
            $p['is_service']   = $p['is_service'] ?? false;
            $p['is_active']    = true;
            $p['created_at']   = now();
            $p['updated_at']   = now();
            $p['description']  = null;
            $p['model_number'] = null;
            $p['barcode']      = null;
            $p['image']        = null;
        }
        DB::table('products')->insertOrIgnore($products);

        $this->command->info('✓ Gigateam company settings seeded');
        $this->command->info('✓ Document sequences seeded (Invoice continues from 23477)');
        $this->command->info('✓ 10 product categories seeded');
        $this->command->info('✓ ' . count($products) . ' products seeded');
    }
}
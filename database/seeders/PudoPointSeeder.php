<?php

namespace Database\Seeders;

use App\Models\PudoPoint;
use Illuminate\Database\Seeder;

class PudoPointSeeder extends Seeder
{
    public function run(): void
    {
        $pudoPoints = [
            // ROMA
            ['pudo_id' => 'PUDO_RM_001', 'name' => 'Tabaccheria Centrale', 'address' => 'Via del Corso 123', 'city' => 'Roma', 'zip_code' => '00186', 'province' => 'RM', 'latitude' => 41.9028, 'longitude' => 12.4964, 'phone' => '06-12345678'],
            ['pudo_id' => 'PUDO_RM_002', 'name' => 'Edicola Termini', 'address' => 'Piazza dei Cinquecento 1', 'city' => 'Roma', 'zip_code' => '00185', 'province' => 'RM', 'latitude' => 41.9010, 'longitude' => 12.5015, 'phone' => '06-23456789'],
            ['pudo_id' => 'PUDO_RM_003', 'name' => 'Cartolibreria Trastevere', 'address' => 'Via della Lungaretta 45', 'city' => 'Roma', 'zip_code' => '00153', 'province' => 'RM', 'latitude' => 41.8897, 'longitude' => 12.4697, 'phone' => '06-34567890'],
            ['pudo_id' => 'PUDO_RM_004', 'name' => 'Punto BRT Prati', 'address' => 'Via Cola di Rienzo 78', 'city' => 'Roma', 'zip_code' => '00192', 'province' => 'RM', 'latitude' => 41.9065, 'longitude' => 12.4598, 'phone' => '06-45678901'],
            ['pudo_id' => 'PUDO_RM_005', 'name' => 'Tabacchi EUR', 'address' => 'Viale Europa 190', 'city' => 'Roma', 'zip_code' => '00144', 'province' => 'RM', 'latitude' => 41.8345, 'longitude' => 12.4686, 'phone' => '06-56789012'],

            // MILANO
            ['pudo_id' => 'PUDO_MI_001', 'name' => 'Edicola Duomo', 'address' => 'Piazza del Duomo 14', 'city' => 'Milano', 'zip_code' => '20121', 'province' => 'MI', 'latitude' => 45.4642, 'longitude' => 9.1900, 'phone' => '02-12345678'],
            ['pudo_id' => 'PUDO_MI_002', 'name' => 'Tabaccheria Centrale', 'address' => 'Via Torino 56', 'city' => 'Milano', 'zip_code' => '20123', 'province' => 'MI', 'latitude' => 45.4608, 'longitude' => 9.1845, 'phone' => '02-23456789'],
            ['pudo_id' => 'PUDO_MI_003', 'name' => 'Punto Ritiro Navigli', 'address' => 'Ripa di Porta Ticinese 23', 'city' => 'Milano', 'zip_code' => '20143', 'province' => 'MI', 'latitude' => 45.4502, 'longitude' => 9.1755, 'phone' => '02-34567890'],
            ['pudo_id' => 'PUDO_MI_004', 'name' => 'Cartolibreria Brera', 'address' => 'Via Fiori Chiari 12', 'city' => 'Milano', 'zip_code' => '20121', 'province' => 'MI', 'latitude' => 45.4719, 'longitude' => 9.1881, 'phone' => '02-45678901'],
            ['pudo_id' => 'PUDO_MI_005', 'name' => 'Edicola Stazione Centrale', 'address' => 'Piazza Duca d\'Aosta 1', 'city' => 'Milano', 'zip_code' => '20124', 'province' => 'MI', 'latitude' => 45.4865, 'longitude' => 9.2040, 'phone' => '02-56789012'],

            // TORINO
            ['pudo_id' => 'PUDO_TO_001', 'name' => 'Tabaccheria Porta Nuova', 'address' => 'Corso Vittorio Emanuele II 53', 'city' => 'Torino', 'zip_code' => '10125', 'province' => 'TO', 'latitude' => 45.0625, 'longitude' => 7.6782, 'phone' => '011-1234567'],
            ['pudo_id' => 'PUDO_TO_002', 'name' => 'Edicola Piazza Castello', 'address' => 'Piazza Castello 1', 'city' => 'Torino', 'zip_code' => '10121', 'province' => 'TO', 'latitude' => 45.0703, 'longitude' => 7.6869, 'phone' => '011-2345678'],
            ['pudo_id' => 'PUDO_TO_003', 'name' => 'Punto BRT San Salvario', 'address' => 'Via Nizza 45', 'city' => 'Torino', 'zip_code' => '10126', 'province' => 'TO', 'latitude' => 45.0536, 'longitude' => 7.6753, 'phone' => '011-3456789'],
            ['pudo_id' => 'PUDO_TO_004', 'name' => 'Cartolibreria Crocetta', 'address' => 'Corso Galileo Ferraris 78', 'city' => 'Torino', 'zip_code' => '10128', 'province' => 'TO', 'latitude' => 45.0489, 'longitude' => 7.6642, 'phone' => '011-4567890'],

            // NAPOLI
            ['pudo_id' => 'PUDO_NA_001', 'name' => 'Tabacchi Piazza Garibaldi', 'address' => 'Piazza Garibaldi 12', 'city' => 'Napoli', 'zip_code' => '80142', 'province' => 'NA', 'latitude' => 40.8518, 'longitude' => 14.2681, 'phone' => '081-1234567'],
            ['pudo_id' => 'PUDO_NA_002', 'name' => 'Edicola Vomero', 'address' => 'Via Scarlatti 89', 'city' => 'Napoli', 'zip_code' => '80127', 'province' => 'NA', 'latitude' => 40.8429, 'longitude' => 14.2145, 'phone' => '081-2345678'],
            ['pudo_id' => 'PUDO_NA_003', 'name' => 'Punto Ritiro Centro Storico', 'address' => 'Via Toledo 234', 'city' => 'Napoli', 'zip_code' => '80134', 'province' => 'NA', 'latitude' => 40.8404, 'longitude' => 14.2488, 'phone' => '081-3456789'],
            ['pudo_id' => 'PUDO_NA_004', 'name' => 'Cartolibreria Chiaia', 'address' => 'Via Chiaia 56', 'city' => 'Napoli', 'zip_code' => '80121', 'province' => 'NA', 'latitude' => 40.8359, 'longitude' => 14.2456, 'phone' => '081-4567890'],

            // FIRENZE
            ['pudo_id' => 'PUDO_FI_001', 'name' => 'Tabaccheria Duomo', 'address' => 'Via dei Calzaiuoli 23', 'city' => 'Firenze', 'zip_code' => '50122', 'province' => 'FI', 'latitude' => 43.7717, 'longitude' => 11.2558, 'phone' => '055-123456'],
            ['pudo_id' => 'PUDO_FI_002', 'name' => 'Edicola Santa Maria Novella', 'address' => 'Piazza della Stazione 1', 'city' => 'Firenze', 'zip_code' => '50123', 'province' => 'FI', 'latitude' => 43.7766, 'longitude' => 11.2486, 'phone' => '055-234567'],
            ['pudo_id' => 'PUDO_FI_003', 'name' => 'Punto BRT Oltrarno', 'address' => 'Via Santo Spirito 12', 'city' => 'Firenze', 'zip_code' => '50125', 'province' => 'FI', 'latitude' => 43.7658, 'longitude' => 11.2489, 'phone' => '055-345678'],

            // BOLOGNA
            ['pudo_id' => 'PUDO_BO_001', 'name' => 'Tabacchi Piazza Maggiore', 'address' => 'Via Rizzoli 12', 'city' => 'Bologna', 'zip_code' => '40125', 'province' => 'BO', 'latitude' => 44.4938, 'longitude' => 11.3428, 'phone' => '051-123456'],
            ['pudo_id' => 'PUDO_BO_002', 'name' => 'Edicola Stazione Centrale', 'address' => 'Piazza delle Medaglie d\'Oro 2', 'city' => 'Bologna', 'zip_code' => '40121', 'province' => 'BO', 'latitude' => 44.5058, 'longitude' => 11.3431, 'phone' => '051-234567'],
            ['pudo_id' => 'PUDO_BO_003', 'name' => 'Cartolibreria Universitaria', 'address' => 'Via Zamboni 45', 'city' => 'Bologna', 'zip_code' => '40126', 'province' => 'BO', 'latitude' => 44.4967, 'longitude' => 11.3522, 'phone' => '051-345678'],

            // GENOVA
            ['pudo_id' => 'PUDO_GE_001', 'name' => 'Tabaccheria Brignole', 'address' => 'Piazza Giuseppe Verdi 1', 'city' => 'Genova', 'zip_code' => '16121', 'province' => 'GE', 'latitude' => 44.4056, 'longitude' => 8.9463, 'phone' => '010-123456'],
            ['pudo_id' => 'PUDO_GE_002', 'name' => 'Edicola Porto Antico', 'address' => 'Via San Lorenzo 23', 'city' => 'Genova', 'zip_code' => '16123', 'province' => 'GE', 'latitude' => 44.4084, 'longitude' => 8.9316, 'phone' => '010-234567'],
            ['pudo_id' => 'PUDO_GE_003', 'name' => 'Punto Ritiro Carignano', 'address' => 'Via Assarotti 12', 'city' => 'Genova', 'zip_code' => '16122', 'province' => 'GE', 'latitude' => 44.4025, 'longitude' => 8.9380, 'phone' => '010-345678'],

            // PALERMO
            ['pudo_id' => 'PUDO_PA_001', 'name' => 'Tabacchi Stazione Centrale', 'address' => 'Piazza Giulio Cesare 1', 'city' => 'Palermo', 'zip_code' => '90123', 'province' => 'PA', 'latitude' => 38.1194, 'longitude' => 13.3656, 'phone' => '091-123456'],
            ['pudo_id' => 'PUDO_PA_002', 'name' => 'Edicola Politeama', 'address' => 'Via Ruggero Settimo 45', 'city' => 'Palermo', 'zip_code' => '90139', 'province' => 'PA', 'latitude' => 38.1229, 'longitude' => 13.3615, 'phone' => '091-234567'],
            ['pudo_id' => 'PUDO_PA_003', 'name' => 'Cartolibreria Vucciria', 'address' => 'Via Roma 123', 'city' => 'Palermo', 'zip_code' => '90133', 'province' => 'PA', 'latitude' => 38.1157, 'longitude' => 13.3615, 'phone' => '091-345678'],

            // BARI
            ['pudo_id' => 'PUDO_BA_001', 'name' => 'Tabaccheria Centrale', 'address' => 'Via Sparano 78', 'city' => 'Bari', 'zip_code' => '70121', 'province' => 'BA', 'latitude' => 41.1171, 'longitude' => 16.8719, 'phone' => '080-123456'],
            ['pudo_id' => 'PUDO_BA_002', 'name' => 'Edicola Stazione', 'address' => 'Piazza Aldo Moro 1', 'city' => 'Bari', 'zip_code' => '70122', 'province' => 'BA', 'latitude' => 41.1089, 'longitude' => 16.8728, 'phone' => '080-234567'],
            ['pudo_id' => 'PUDO_BA_003', 'name' => 'Punto BRT Poggiofranco', 'address' => 'Via Amendola 45', 'city' => 'Bari', 'zip_code' => '70126', 'province' => 'BA', 'latitude' => 41.1025, 'longitude' => 16.8825, 'phone' => '080-345678'],

            // VERONA
            ['pudo_id' => 'PUDO_VR_001', 'name' => 'Tabacchi Piazza Bra', 'address' => 'Via Mazzini 12', 'city' => 'Verona', 'zip_code' => '37121', 'province' => 'VR', 'latitude' => 45.4408, 'longitude' => 10.9916, 'phone' => '045-123456'],
            ['pudo_id' => 'PUDO_VR_002', 'name' => 'Edicola Porta Nuova', 'address' => 'Piazzale XXV Aprile 1', 'city' => 'Verona', 'zip_code' => '37138', 'province' => 'VR', 'latitude' => 45.4286, 'longitude' => 10.9825, 'phone' => '045-234567'],

            // PADOVA
            ['pudo_id' => 'PUDO_PD_001', 'name' => 'Tabaccheria Prato della Valle', 'address' => 'Prato della Valle 23', 'city' => 'Padova', 'zip_code' => '35123', 'province' => 'PD', 'latitude' => 45.3977, 'longitude' => 11.8760, 'phone' => '049-123456'],
            ['pudo_id' => 'PUDO_PD_002', 'name' => 'Edicola Stazione', 'address' => 'Piazzale Stazione 1', 'city' => 'Padova', 'zip_code' => '35131', 'province' => 'PD', 'latitude' => 45.4170, 'longitude' => 11.8808, 'phone' => '049-234567'],

            // CATANIA
            ['pudo_id' => 'PUDO_CT_001', 'name' => 'Tabacchi Piazza Duomo', 'address' => 'Via Etnea 123', 'city' => 'Catania', 'zip_code' => '95124', 'province' => 'CT', 'latitude' => 37.5024, 'longitude' => 15.0873, 'phone' => '095-123456'],
            ['pudo_id' => 'PUDO_CT_002', 'name' => 'Edicola Stazione Centrale', 'address' => 'Piazza Papa Giovanni XXIII 1', 'city' => 'Catania', 'zip_code' => '95129', 'province' => 'CT', 'latitude' => 37.5067, 'longitude' => 15.0826, 'phone' => '095-234567'],

            // VENEZIA
            ['pudo_id' => 'PUDO_VE_001', 'name' => 'Tabaccheria Rialto', 'address' => 'Ruga degli Orefici 45', 'city' => 'Venezia', 'zip_code' => '30125', 'province' => 'VE', 'latitude' => 45.4380, 'longitude' => 12.3358, 'phone' => '041-123456'],
            ['pudo_id' => 'PUDO_VE_002', 'name' => 'Edicola Santa Lucia', 'address' => 'Fondamenta Santa Lucia 1', 'city' => 'Venezia', 'zip_code' => '30121', 'province' => 'VE', 'latitude' => 45.4419, 'longitude' => 12.3206, 'phone' => '041-234567'],
            ['pudo_id' => 'PUDO_VE_003', 'name' => 'Punto Ritiro Mestre', 'address' => 'Via Piave 23', 'city' => 'Venezia', 'zip_code' => '30171', 'province' => 'VE', 'latitude' => 45.4897, 'longitude' => 12.2436, 'phone' => '041-345678'],
        ];

        foreach ($pudoPoints as $point) {
            PudoPoint::updateOrCreate(
                ['pudo_id' => $point['pudo_id']],
                array_merge($point, [
                    'country' => 'ITA',
                    'is_active' => true,
                    'opening_hours' => [
                        'monday' => '09:00-19:00',
                        'tuesday' => '09:00-19:00',
                        'wednesday' => '09:00-19:00',
                        'thursday' => '09:00-19:00',
                        'friday' => '09:00-19:00',
                        'saturday' => '09:00-13:00',
                        'sunday' => 'Chiuso',
                    ],
                ])
            );
        }

        $this->command->info('✓ Creati ' . count($pudoPoints) . ' punti PUDO di fallback');
    }
}

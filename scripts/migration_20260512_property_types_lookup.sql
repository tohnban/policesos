-- Migration: Convert property types from ENUM to Lookup Table
-- Date: 2026-05-12
-- Purpose: Replace hard-coded ENUM with flexible lookup table for better maintainability

-- Step 1: Create property_types lookup table
CREATE TABLE property_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    label_pt VARCHAR(100) NOT NULL,
    category VARCHAR(50) NOT NULL,
    icon VARCHAR(50),
    color VARCHAR(7),
    is_legacy BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_code (code),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Step 2: Insert legacy types (for backward compatibility)
INSERT INTO property_types (code, label_pt, category, icon, is_legacy) VALUES
('casa', 'Casa', 'residential', 'home', TRUE),
('edificio', 'Edifício', 'residential', 'building', TRUE),
('vivenda', 'Vivenda', 'residential', 'mansion', TRUE),
('terreno', 'Terreno', 'land', 'map', TRUE),
('apartamento', 'Apartamento', 'residential', 'door', TRUE);

-- Step 3: Insert new residential types
INSERT INTO property_types (code, label_pt, category, icon) VALUES
('moradia_simples', 'Moradia Simples', 'residential', 'home-alt'),
('moradia_geminada', 'Moradia Geminada', 'residential', 'home-heart'),
('moradia_duplex', 'Moradia Duplex', 'residential', 'home-lg-alt'),
('estudio_kitnet', 'Estúdio/Kitnet', 'residential', 'door-open'),
('loft', 'Loft', 'residential', 'building'),
('condominio_fechado_unidade', 'Condomínio Fechado (Unidade)', 'residential', 'fence'),
('quarto_suite', 'Quarto/Suite', 'residential', 'door-closed');

-- Step 4: Insert commercial types
INSERT INTO property_types (code, label_pt, category, icon) VALUES
('loja', 'Loja', 'commercial', 'shop'),
('escritorio', 'Escritório', 'commercial', 'building-check'),
('sala_comercial', 'Sala Comercial', 'commercial', 'door'),
('pavilhao_armazem', 'Pavilhão/Armazém', 'commercial', 'boxes'),
('galpao', 'Galpão', 'commercial', 'boxes'),
('centro_comercial_loja_interna', 'Centro Comercial (Loja Interna)', 'commercial', 'shop-window'),
('quiosque', 'Quiosque', 'commercial', 'shop-small');

-- Step 5: Insert industrial types
INSERT INTO property_types (code, label_pt, category, icon) VALUES
('fabrica', 'Fábrica', 'industrial', 'cog'),
('parque_industrial', 'Parque Industrial', 'industrial', 'cogs'),
('armazem_logistico', 'Armazém Logístico', 'industrial', 'boxes-stacked'),
('deposito', 'Depósito', 'industrial', 'boxes');

-- Step 6: Insert land types
INSERT INTO property_types (code, label_pt, category, icon) VALUES
('terreno_urbano', 'Terreno Urbano', 'land', 'map-pin'),
('loteamento', 'Loteamento', 'land', 'map-quad'),
('terreno_rural', 'Terreno Rural', 'land', 'map-tree'),
('terreno_agricola', 'Terreno Agrícola', 'land', 'map-grain'),
('terreno_industrial', 'Terreno Industrial', 'land', 'map-factory');

-- Step 7: Insert tourism types
INSERT INTO property_types (code, label_pt, category, icon) VALUES
('hotel', 'Hotel', 'tourism', 'bed-front'),
('motel', 'Motel', 'tourism', 'bed'),
('hostel', 'Hostel', 'tourism', 'bed-alt'),
('apart_hotel', 'Apart Hotel', 'tourism', 'bed-filled'),
('alojamento_local', 'Alojamento Local', 'tourism', 'house-door'),
('guest_house', 'Guest House', 'tourism', 'house-fill');

-- Step 8: Insert institutional types
INSERT INTO property_types (code, label_pt, category, icon) VALUES
('escola', 'Escola', 'institutional', 'book'),
('hospital_clinica', 'Hospital/Clínica', 'institutional', 'heart-pulse'),
('igreja_templo', 'Igreja/Templo', 'institutional', 'cross'),
('predio_publico', 'Prédio Público', 'institutional', 'building-columns'),
('centro_comunitario', 'Centro Comunitário', 'institutional', 'people');

-- Step 9: Insert complementary types
INSERT INTO property_types (code, label_pt, category, icon) VALUES
('garagem', 'Garagem', 'complementary', 'car'),
('estacionamento', 'Estacionamento', 'complementary', 'parking'),
('arrecadacao_anexo', 'Arrecadação/Anexo', 'complementary', 'box'),
('box', 'Box', 'complementary', 'boxes');

-- Step 10: Modify properties table to use VARCHAR with FK
ALTER TABLE properties 
MODIFY COLUMN type VARCHAR(50) NOT NULL;

-- Step 11: Add foreign key constraint
ALTER TABLE properties
ADD CONSTRAINT fk_property_type
FOREIGN KEY (type) REFERENCES property_types(code)
ON DELETE RESTRICT
ON UPDATE CASCADE;

-- Step 12: Update FOREIGN_KEY_CHECKS and add index
CREATE INDEX idx_properties_type ON properties(type);

CREATE DATABASE IF NOT EXISTS medicinescan
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE medicinescan;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(120) NOT NULL UNIQUE,
    full_name VARCHAR(120) NOT NULL,
    username VARCHAR(30) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    age TINYINT UNSIGNED NOT NULL,
    sex ENUM('Femenino', 'Masculino', 'Otro', 'Prefiero no decir') NOT NULL,
    allergies VARCHAR(500) NOT NULL DEFAULT 'Ninguna',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS medicines (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL UNIQUE,
    aliases VARCHAR(250) NULL,
    general_warning VARCHAR(500) NOT NULL
);

CREATE TABLE IF NOT EXISTS api_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS medicine_documents (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    medicine_id INT UNSIGNED NOT NULL,
    title VARCHAR(150) NOT NULL,
    content TEXT NOT NULL,
    source_title VARCHAR(180) NOT NULL,
    source_url VARCHAR(500) NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_medicine_document (medicine_id, title),
    FOREIGN KEY (medicine_id) REFERENCES medicines(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS medicine_embeddings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    document_id INT UNSIGNED NOT NULL,
    model VARCHAR(100) NOT NULL,
    vector_json LONGTEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_document_model (document_id, model),
    FOREIGN KEY (document_id) REFERENCES medicine_documents(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS scan_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    medicine_id INT UNSIGNED NOT NULL,
    ocr_text TEXT NOT NULL,
    result_level ENUM('safe', 'warning', 'danger') NOT NULL,
    rule_message VARCHAR(500) NOT NULL,
    ai_message TEXT NOT NULL,
    generated_by ENUM('rules', 'ollama') NOT NULL DEFAULT 'rules',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (medicine_id) REFERENCES medicines(id)
);

INSERT INTO medicines (name, aliases, general_warning) VALUES
('Omeprazol', 'omeprazole', 'Revisar otros medicamentos y la duración del tratamiento.'),
('Paracetamol', 'acetaminofén,acetaminophen', 'Evitar exceder la dosis indicada y considerar problemas del hígado.'),
('Aspirina', 'ácido acetilsalicílico,AAS', 'Precaución en menores, sangrado, embarazo y alergia a AINEs.'),
('Olanzapina', 'zyprexa', 'Medicamento de prescripción; no iniciar ni suspender sin supervisión médica.'),
('Ibuprofeno', 'ibuprofen,advil', 'Precaución por alergia a AINEs, estómago, riñón y embarazo.')
ON DUPLICATE KEY UPDATE aliases=VALUES(aliases), general_warning=VALUES(general_warning);

INSERT INTO medicine_documents (medicine_id, title, content, source_title, source_url)
SELECT id, 'Información general', 'El omeprazol reduce la producción de ácido del estómago. Deben revisarse otros medicamentos y la duración del tratamiento con un profesional.', 'MedlinePlus: Omeprazol', 'https://medlineplus.gov/druginfo/meds/a693050.html'
FROM medicines WHERE name='Omeprazol'
ON DUPLICATE KEY UPDATE content=VALUES(content), source_url=VALUES(source_url);

INSERT INTO medicine_documents (medicine_id, title, content, source_title, source_url)
SELECT id, 'Precauciones generales', 'El paracetamol o acetaminofén se usa para dolor y fiebre. Exceder la cantidad indicada puede causar daño grave, especialmente al hígado.', 'MedlinePlus: Acetaminofén', 'https://medlineplus.gov/druginfo/meds/a681004.html'
FROM medicines WHERE name='Paracetamol'
ON DUPLICATE KEY UPDATE content=VALUES(content), source_url=VALUES(source_url);

INSERT INTO medicine_documents (medicine_id, title, content, source_title, source_url)
SELECT id, 'Precauciones generales', 'La aspirina pertenece a los salicilatos. Requiere precaución por alergias, sangrado y uso en menores; debe consultarse a un profesional.', 'MedlinePlus: Aspirina', 'https://medlineplus.gov/druginfo/meds/a682878.html'
FROM medicines WHERE name='Aspirina'
ON DUPLICATE KEY UPDATE content=VALUES(content), source_url=VALUES(source_url);

INSERT INTO medicine_documents (medicine_id, title, content, source_title, source_url)
SELECT id, 'Uso bajo supervisión', 'La olanzapina es un antipsicótico de prescripción. No debe iniciarse, cambiarse ni suspenderse sin supervisión profesional.', 'MedlinePlus: Olanzapina', 'https://medlineplus.gov/druginfo/meds/a601213.html'
FROM medicines WHERE name='Olanzapina'
ON DUPLICATE KEY UPDATE content=VALUES(content), source_url=VALUES(source_url);

INSERT INTO medicine_documents (medicine_id, title, content, source_title, source_url)
SELECT id, 'Precauciones generales', 'El ibuprofeno es un AINE. Puede requerir precaución por alergias a AINEs y riesgos gastrointestinales, cardiovasculares o renales.', 'MedlinePlus: Ibuprofeno', 'https://medlineplus.gov/druginfo/meds/a682159.html'
FROM medicines WHERE name='Ibuprofeno'
ON DUPLICATE KEY UPDATE content=VALUES(content), source_url=VALUES(source_url);

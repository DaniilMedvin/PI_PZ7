DROP TABLE IF EXISTS deals;
DROP TABLE IF EXISTS properties;
DROP TABLE IF EXISTS agents;

CREATE TABLE agents (
    id SERIAL PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    phone VARCHAR(20) NOT NULL
);

CREATE TABLE properties (
    id SERIAL PRIMARY KEY,
    agent_id INTEGER NOT NULL REFERENCES agents(id) ON DELETE RESTRICT,
    address VARCHAR(200) NOT NULL,
    type VARCHAR(40) NOT NULL,
    price NUMERIC(12,2) NOT NULL CHECK (price > 0)
);

CREATE TABLE deals (
    id SERIAL PRIMARY KEY,
    property_id INTEGER NOT NULL REFERENCES properties(id) ON DELETE RESTRICT,
    client_name VARCHAR(120) NOT NULL,
    deal_date DATE NOT NULL
);

INSERT INTO agents (full_name, phone) VALUES
    ('Коваленко Олена Петрівна', '+380501112233'),
    ('Шевченко Андрій Іванович', '+380672223344'),
    ('Бондаренко Марія Олегівна', '+380933334455');

INSERT INTO properties (agent_id, address, type, price) VALUES
    (1, 'Київ, вул. Хрещатик, 12, кв. 45', 'Квартира', 125000.00),
    (1, 'Київ, вул. Липська, 8', 'Комерційна', 310000.00),
    (2, 'Львів, вул. Личаківська, 21', 'Будинок', 189000.00),
    (3, 'Одеса, вул. Дерибасівська, 5, кв. 9', 'Квартира', 98000.00),
    (3, 'Харків, пров. Тихий, 3', 'Земельна ділянка', 42000.00);

INSERT INTO deals (property_id, client_name, deal_date) VALUES
    (1, 'Мельник Сергій Васильович', '2026-09-14'),
    (3, 'Гриценко Ірина Миколаївна', '2026-10-02'),
    (4, 'Ткаченко Павло Андрійович', '2026-10-27');

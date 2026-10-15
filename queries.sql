-- ============================================================
-- E-commerce Database System | PostgreSQL
-- Example queries from the Baze podataka 2 project documentation
-- Section 4: 3 simple queries and 3 complex queries
-- ============================================================

SET search_path TO projekt;

-- 4.1. Jednostavni upiti (Simple queries)

-- Count orders currently in status 'Kreirano'.
SELECT COUNT(*)
FROM narudzba
WHERE status = 'Kreirano';

-- Total stock quantity across warehouses.
SELECT SUM(kolicina)
FROM zaliha;

-- Highest unit price of a product.
SELECT MAX(jed_cijena)
FROM proizvod;


-- 4.2. Složeni upiti (Complex queries)

-- Customers and their orders, including order date, status, and total.
SELECT korisnik.ime, korisnik.prezime,
       narudzba.datum_narudzbe,
       narudzba.status,
       narudzba.ukupno
FROM korisnik
JOIN narudzba ON korisnik.korisnik_id = narudzba.korisnik_id;

-- Products, their unit prices, and their categories.
SELECT proizvod.naziv_proizvoda,
       proizvod.jed_cijena,
       kategorija.naziv_kategorije
FROM proizvod
JOIN kategorija ON proizvod.kategorija_id = kategorija.kategorija_id;

-- Total stock quantity by warehouse and product category.
SELECT s.naziv_skladista,
       k.naziv_kategorije,
       SUM(z.kolicina) AS ukupno_kolicina
FROM skladiste s
JOIN zaliha z ON z.skladiste_id = s.skladiste_id
JOIN proizvod p ON p.proizvod_id = z.proizvod_id
JOIN kategorija k ON k.kategorija_id = p.kategorija_id
GROUP BY s.naziv_skladista, k.naziv_kategorije
ORDER BY s.naziv_skladista, ukupno_kolicina DESC;

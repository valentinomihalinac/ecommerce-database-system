-- ============================================================
-- E-commerce Database System | PostgreSQL
-- Triggers from the Baze podataka 2 project documentation
--
-- Run database.sql first to create the tables.
-- This script creates 2 trigger functions and 2 triggers.
-- ============================================================

SET search_path TO projekt;

-- 1. Calculate and update the order total whenever an order item
--    is inserted, updated, or deleted.
CREATE OR REPLACE FUNCTION izracun_ukupno_narudzbe()
RETURNS trigger
AS
$$
DECLARE
    v_narudzba_id INTEGER;
    novi_ukupno NUMERIC(12,2);
BEGIN
    v_narudzba_id := COALESCE(NEW.narudzba_id, OLD.narudzba_id);

    SELECT COALESCE(SUM(kolicina * cijena_u_trenutku), 0)
    INTO novi_ukupno
    FROM stavke_narudzbe
    WHERE narudzba_id = v_narudzba_id;

    UPDATE narudzba
    SET ukupno = novi_ukupno
    WHERE narudzba_id = v_narudzba_id;

    RAISE NOTICE 'Ukupni iznos narudžbe % iznosi % EUR.',
        v_narudzba_id, novi_ukupno;

    RETURN COALESCE(NEW, OLD);
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_izracun_ukupno
AFTER INSERT OR UPDATE OR DELETE
ON stavke_narudzbe
FOR EACH ROW
EXECUTE PROCEDURE izracun_ukupno_narudzbe();


-- 2. Update inventory across warehouses when an order item is
--    inserted, changed, or deleted. Reject insufficient stock.
CREATE OR REPLACE FUNCTION smanji_zalihu_po_skladistima()
RETURNS trigger
AS
$$
DECLARE
    preostalo INTEGER;
    r RECORD;
    uzmi INTEGER;
    prvo_skladiste INTEGER;
BEGIN
    IF TG_OP = 'INSERT' OR (TG_OP = 'UPDATE' AND NEW.kolicina > OLD.kolicina)
    THEN
        IF TG_OP = 'INSERT' THEN
            preostalo := NEW.kolicina;
        ELSE
            preostalo := NEW.kolicina - OLD.kolicina;
        END IF;

        FOR r IN
            SELECT skladiste_id, kolicina
            FROM zaliha
            WHERE proizvod_id = NEW.proizvod_id
              AND kolicina > 0
            ORDER BY skladiste_id
            FOR UPDATE
        LOOP
            EXIT WHEN preostalo <= 0;

            uzmi := LEAST(preostalo, r.kolicina);

            UPDATE zaliha
            SET kolicina = kolicina - uzmi
            WHERE proizvod_id = NEW.proizvod_id
              AND skladiste_id = r.skladiste_id;

            preostalo := preostalo - uzmi;
        END LOOP;

        IF preostalo > 0 THEN
            RAISE EXCEPTION
                'Nema dovoljno zalihe za proizvod %. Nedostaje %.',
                NEW.proizvod_id, preostalo;
        END IF;

        RETURN NEW;
    END IF;

    IF TG_OP = 'DELETE' OR (TG_OP = 'UPDATE' AND NEW.kolicina < OLD.kolicina)
    THEN
        SELECT MIN(skladiste_id)
        INTO prvo_skladiste
        FROM skladiste;

        IF TG_OP = 'DELETE' THEN
            preostalo := OLD.kolicina;
        ELSE
            preostalo := OLD.kolicina - NEW.kolicina;
        END IF;

        UPDATE zaliha
        SET kolicina = kolicina + preostalo
        WHERE proizvod_id = OLD.proizvod_id
          AND skladiste_id = prvo_skladiste;

        IF NOT FOUND THEN
            INSERT INTO zaliha (proizvod_id, skladiste_id, kolicina)
            VALUES (OLD.proizvod_id, prvo_skladiste, preostalo);
        END IF;

        RETURN COALESCE(NEW, OLD);
    END IF;

    RETURN COALESCE(NEW, OLD);
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_smanji_zalihu
BEFORE INSERT OR UPDATE OR DELETE
ON stavke_narudzbe
FOR EACH ROW
EXECUTE PROCEDURE smanji_zalihu_po_skladistima();

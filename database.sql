-- ============================================================
-- E-commerce Database System | PostgreSQL
-- University project: Baze podataka 2 (FOI)
--
-- Database schema and table definitions.
-- Run this script in the target PostgreSQL database.
-- Requires permission to create/use schema "projekt".
-- Functions and triggers are provided separately in triggers.sql.
-- Sample data and SELECT queries are not included.
-- ============================================================

CREATE SCHEMA IF NOT EXISTS projekt;
SET search_path TO projekt;

create table korisnik
(
    korisnik_id serial
        primary key,
    ime         varchar(50)  not null,
    prezime     varchar(50)  not null,
    email       varchar(150) not null
        unique,
    lozinka     varchar(255) not null
);

create table adresa
(
    adresa_id   serial
        primary key,
    ulica       varchar(120) not null,
    grad        varchar(80)  not null,
    pbr         varchar(20)  not null,
    drzava      varchar(60)  not null,
    korisnik_id integer      not null
        constraint adresa_korisnik_fk
            references korisnik
            on update cascade on delete cascade
);

create table kategorija
(
    kategorija_id    serial
        primary key,
    naziv_kategorije varchar(80) not null
        unique,
    pripada          integer
        constraint kategorija_roditelj_fk
            references kategorija
            on update cascade on delete set null,
    constraint kategorija_nije_sama_sebi
        check ((pripada IS NULL) OR (pripada <> kategorija_id))
);

create table proizvod
(
    proizvod_id     serial
        primary key,
    naziv_proizvoda varchar(120)   not null,
    jed_cijena      numeric(12, 2) not null
        constraint proizvod_jed_cijena_check
            check (jed_cijena >= (0)::numeric),
    kategorija_id   integer        not null
        constraint proizvod_kategorija_fk
            references kategorija
            on update cascade on delete restrict
);

create table skladiste
(
    skladiste_id    serial
        primary key,
    naziv_skladista varchar(80) not null,
    lokacija        varchar(150)
);

create table zaliha
(
    proizvod_id  integer not null
        constraint zaliha_proizvod_fk
            references proizvod
            on update cascade on delete restrict,
    skladiste_id integer not null
        constraint zaliha_skladiste_fk
            references skladiste
            on update cascade on delete restrict,
    kolicina     integer not null
        constraint zaliha_kolicina_check
            check (kolicina >= 0),
    constraint zaliha_pk
        primary key (proizvod_id, skladiste_id)
);

create table narudzba
(
    narudzba_id    serial
        primary key,
    datum_narudzbe timestamp                                            not null,
    status         varchar(20)    default 'Kreirano'::character varying not null
        constraint narudzba_status_check
            check ((status)::text = ANY
                   ((ARRAY ['Kreirano'::character varying, 'Obrada'::character varying, 'Placeno'::character varying, 'Poslano'::character varying, 'Isporuceno'::character varying, 'Otkazano'::character varying])::text[])),
    ukupno         numeric(12, 2) default 0                             not null
        constraint narudzba_ukupno_check
            check (ukupno >= (0)::numeric),
    adresa_id      integer                                              not null
        constraint narudzba_adresa_fk
            references adresa
            on update cascade on delete restrict,
    korisnik_id    integer                                              not null
        constraint narudzba_korisnik_fk
            references korisnik
            on update cascade on delete restrict
);

create table stavke_narudzbe
(
    proizvod_id       integer        not null
        constraint stavke_proizvod_fk
            references proizvod
            on update cascade on delete restrict,
    narudzba_id       integer        not null
        constraint stavke_narudzba_fk
            references narudzba
            on update cascade on delete cascade,
    kolicina          integer        not null
        constraint stavke_narudzbe_kolicina_check
            check (kolicina > 0),
    cijena_u_trenutku numeric(12, 2) not null
        constraint stavke_narudzbe_cijena_u_trenutku_check
            check (cijena_u_trenutku >= (0)::numeric),
    constraint stavke_narudzbe_pk
        primary key (proizvod_id, narudzba_id)
);

create table dostava
(
    dostava_id     serial
        primary key,
    nacin_dostave  varchar(40)              not null,
    cijena_dostave numeric(12, 2) default 0 not null
        constraint dostava_cijena_dostave_check
            check (cijena_dostave >= (0)::numeric),
    datum_slanja   timestamp,
    narudzba_id    integer                  not null
        unique
        constraint dostava_narudzba_fk
            references narudzba
            on update cascade on delete cascade
);

create table placanje
(
    placanje_id      serial
        primary key,
    nacin_placanja   varchar(20)                                      not null
        constraint placanje_nacin_placanja_check
            check ((nacin_placanja)::text = ANY
                   ((ARRAY ['Kartica'::character varying, 'Pouzece'::character varying])::text[])),
    iznos            numeric(12, 2)                                   not null
        constraint placanje_iznos_check
            check (iznos >= (0)::numeric),
    status_placanja  varchar(20) default 'Ceka se'::character varying not null
        constraint placanje_status_placanja_check
            check ((status_placanja)::text = ANY
                   ((ARRAY ['Ceka se'::character varying, 'Placeno'::character varying, 'Otkazano'::character varying])::text[])),
    vrijeme_placanja timestamp,
    narudzba_id      integer                                          not null
        unique
        constraint placanje_narudzba_fk
            references narudzba
            on update cascade on delete cascade
);

-- ================================
-- ȘTERGEREA TABELOR EXISTENTE
-- ================================
BEGIN
  EXECUTE IMMEDIATE 'DROP TABLE IstoricActiuni CASCADE CONSTRAINTS';
  EXECUTE IMMEDIATE 'DROP TABLE Rezervari CASCADE CONSTRAINTS';
  EXECUTE IMMEDIATE 'DROP TABLE Carti CASCADE CONSTRAINTS';
  EXECUTE IMMEDIATE 'DROP TABLE Utilizatori CASCADE CONSTRAINTS';
  EXECUTE IMMEDIATE 'DROP SEQUENCE seq_utilizatori';
  EXECUTE IMMEDIATE 'DROP SEQUENCE seq_carti';
  EXECUTE IMMEDIATE 'DROP SEQUENCE seq_rezervari';
  EXECUTE IMMEDIATE 'DROP SEQUENCE seq_istoric';
EXCEPTION
  WHEN OTHERS THEN NULL;
END;
/

-- ================================
-- CREARE SEQUENCE-URI
-- ================================
CREATE SEQUENCE seq_utilizatori START WITH 1 INCREMENT BY 1;
CREATE SEQUENCE seq_carti START WITH 1 INCREMENT BY 1;
CREATE SEQUENCE seq_rezervari START WITH 1 INCREMENT BY 1;
CREATE SEQUENCE seq_istoric START WITH 1 INCREMENT BY 1;

-- ================================
-- CREARE TABELE
-- ================================

CREATE TABLE Utilizatori (
    id_user NUMBER PRIMARY KEY,
    nume VARCHAR2(100) NOT NULL,
    email VARCHAR2(100) UNIQUE NOT NULL,
    parola VARCHAR2(100) NOT NULL,
    rol VARCHAR2(10) CHECK (rol IN ('user', 'admin')) NOT NULL
);

CREATE TABLE Carti (
    id_carte NUMBER PRIMARY KEY,
    titlu VARCHAR2(200) NOT NULL,
    autor VARCHAR2(100),
    nr_exemplare NUMBER NOT NULL CHECK (nr_exemplare >= 0)
);

CREATE TABLE Rezervari (
    id_rezervare NUMBER PRIMARY KEY,
    id_user NUMBER NOT NULL,
    id_carte NUMBER NOT NULL,
    data_rezervare DATE DEFAULT SYSDATE,
    status VARCHAR2(20) DEFAULT 'activ' CHECK (status IN ('activ', 'anulat', 'expirat')),
    CONSTRAINT fk_rez_user FOREIGN KEY (id_user) REFERENCES Utilizatori(id_user),
    CONSTRAINT fk_rez_carte FOREIGN KEY (id_carte) REFERENCES Carti(id_carte)
);

CREATE TABLE IstoricActiuni (
    id_actiune NUMBER PRIMARY KEY,
    id_user NUMBER NOT NULL,
    actiune VARCHAR2(200) NOT NULL,
    timp TIMESTAMP DEFAULT SYSTIMESTAMP,
    CONSTRAINT fk_hist_user FOREIGN KEY (id_user) REFERENCES Utilizatori(id_user)
);

-- ================================
-- TRIGGERE AUTO-INCREMENT
-- ================================

CREATE OR REPLACE TRIGGER trg_utilizatori_ai
BEFORE INSERT ON Utilizatori
FOR EACH ROW
BEGIN
  IF :NEW.id_user IS NULL THEN
    :NEW.id_user := seq_utilizatori.NEXTVAL;
  END IF;
END;
/

CREATE OR REPLACE TRIGGER trg_carti_ai
BEFORE INSERT ON Carti
FOR EACH ROW
BEGIN
  IF :NEW.id_carte IS NULL THEN
    :NEW.id_carte := seq_carti.NEXTVAL;
  END IF;
END;
/

CREATE OR REPLACE TRIGGER trg_rezervari_ai
BEFORE INSERT ON Rezervari
FOR EACH ROW
BEGIN
  IF :NEW.id_rezervare IS NULL THEN
    :NEW.id_rezervare := seq_rezervari.NEXTVAL;
  END IF;
END;
/

CREATE OR REPLACE TRIGGER trg_istoric_ai
BEFORE INSERT ON IstoricActiuni
FOR EACH ROW
BEGIN
  IF :NEW.id_actiune IS NULL THEN
    :NEW.id_actiune := seq_istoric.NEXTVAL;
  END IF;
END;
/

-- ================================
-- POPULARE CU DATE DE TEST
-- ================================

-- Utilizatori: 1 admin, 2 useri
INSERT INTO Utilizatori (nume, email, parola, rol) VALUES ('Ana Popescu', 'ana@email.com', 'parola123', 'user');
INSERT INTO Utilizatori (nume, email, parola, rol) VALUES ('Mihai Ionescu', 'mihai@email.com', '1234', 'user');
INSERT INTO Utilizatori (nume, email, parola, rol) VALUES ('Admin Biblioteca', 'admin@lib.com', 'adminpass', 'admin');

-- Cărți
INSERT INTO Carti (titlu, autor, nr_exemplare) VALUES ('Ion', 'Liviu Rebreanu', 3);
INSERT INTO Carti (titlu, autor, nr_exemplare) VALUES ('Moromeții', 'Marin Preda', 2);
INSERT INTO Carti (titlu, autor, nr_exemplare) VALUES ('Baltagul', 'Mihail Sadoveanu', 5);










-- ================================
-- FUNCTIE: calculează exemplare disponibile
-- ================================
CREATE OR REPLACE FUNCTION Exemplare_Disponibile(p_id_carte IN NUMBER)
RETURN NUMBER IS
  v_total NUMBER;
  v_rezervate NUMBER;
BEGIN
  SELECT nr_exemplare INTO v_total FROM Carti WHERE id_carte = p_id_carte;
  
  SELECT COUNT(*) INTO v_rezervate
  FROM Rezervari
  WHERE id_carte = p_id_carte AND status = 'activ';
  
  RETURN v_total - v_rezervate;
EXCEPTION
  WHEN NO_DATA_FOUND THEN
    RETURN -1;
END;
/

-- ================================
-- PROCEDURA: rezervă carte cu validări
-- ================================
CREATE OR REPLACE PROCEDURE Rezerva_Carte(
  p_id_user IN NUMBER,
  p_id_carte IN NUMBER
) IS
  v_count NUMBER;
  v_disp NUMBER;
BEGIN
  SELECT COUNT(*) INTO v_count
  FROM Rezervari
  WHERE id_user = p_id_user AND id_carte = p_id_carte AND status = 'activ';

  IF v_count > 0 THEN
    RAISE_APPLICATION_ERROR(-20001, 'Ai deja o rezervare activă pentru această carte.');
  END IF;

  v_disp := Exemplare_Disponibile(p_id_carte);

  IF v_disp <= 0 THEN
    RAISE_APPLICATION_ERROR(-20002, 'Nu mai sunt exemplare disponibile pentru această carte.');
  END IF;

  INSERT INTO Rezervari (id_user, id_carte) VALUES (p_id_user, p_id_carte);

  INSERT INTO IstoricActiuni (id_user, actiune)
  VALUES (p_id_user, 'A rezervat cartea cu ID ' || p_id_carte);
END;
/

-- ================================
-- TRIGGER: logare rezervări în istoric (opțional dacă ai deja în procedură)
-- ================================
CREATE OR REPLACE TRIGGER trg_log_rezervari
AFTER INSERT ON Rezervari
FOR EACH ROW
BEGIN
  INSERT INTO IstoricActiuni (id_user, actiune)
  VALUES (:NEW.id_user, 'A rezervat cartea cu ID ' || :NEW.id_carte);
END;
/

-- ================================
-- TRIGGER: actualizează stoc la anulare
-- ================================
CREATE OR REPLACE TRIGGER trg_actualizeaza_stoc
AFTER UPDATE OF status ON Rezervari
FOR EACH ROW
WHEN (OLD.status = 'activ' AND NEW.status = 'anulat')
BEGIN
  INSERT INTO IstoricActiuni (id_user, actiune)
  VALUES (:OLD.id_user, 'A anulat rezervarea la cartea cu ID ' || :OLD.id_carte);
END;
/


























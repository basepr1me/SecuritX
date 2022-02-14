CREATE TABLE members (
	id INTEGER PRIMARY KEY AUTOINCREMENT,
	first TEXT NOT NULL,
	last TEXT NOT NULL,
	office TEXT NOT NULL,
	email TEXT NOT NULL,
	v_key UUID,
	u_key UUID,
	verified INTEGER NOT NULL,
	moddate NUMERIC NOT NULL,
	company_id INTEGER NOT NULL
);

CREATE TABLE companies (
	company_id INTEGER PRIMARY KEY AUTOINCREMENT,
	name TEXT NOT NULL,
	short TEXT NOT NULL
);

INSERT INTO companies (name, short) VALUES ('Cool Company', 'CC');
INSERT INTO companies (name, short) VALUES ('Radical Job', 'RJ');
INSERT INTO companies (name, short) VALUES ('Gnarly Career', 'GC');

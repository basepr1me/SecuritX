CREATE TABLE members (
	id INTEGER PRIMARY KEY AUTOINCREMENT,
	first TEXT NOT NULL,
	last TEXT NOT NULL,
	office TEXT,
	email TEXT NOT NULL,
	v_key UUID,
	u_key UUID,
	verified INTEGER NOT NULL,
	moddate NUMERIC NOT NULL,
	company_id INTEGER NOT NULL,
	is_admin INTEGER,
	is_editor INTEGER,
	inviter INTEGER,
	r_admin INTEGER,
	r_editor INTEGER,
	ip_address TEXT,
	twofa INTEGER,
	twofa_moddate INTEGER,
	phone INTEGER,
	blocked INTEGER
);

CREATE TABLE companies (
	company_id INTEGER PRIMARY KEY AUTOINCREMENT,
	name TEXT NOT NULL,
	short TEXT NOT NULL,
	domain TEXT NOT NULL,
	phone TEXT NOT NULL,
	downloads INTEGER,
);

CREATE TABLE downloads (
	downloads_id INTEGER PRIMARY KEY AUTOINCREMENT,
	moddate NUMERIC NOT NULL,
	id_key UUID NOT NULL,
	u_key UUID NOT NULL,
	e_key UUID NOT NULL,
	company_id INTEGER NOT NULL,
	downloaded INTEGER
);

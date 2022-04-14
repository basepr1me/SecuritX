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
	company_id INTEGER NOT NULL,
	is_admin INTEGER NOT NULL,
	is_editor INTEGER NOT NULL,
	inviter TEXT,
	r_admin INTEGER,
	r_editor INTEGER,
);

CREATE TABLE companies (
	company_id INTEGER PRIMARY KEY AUTOINCREMENT,
	name TEXT NOT NULL,
	short TEXT NOT NULL,
	domain TEXT NOT NULL
);

CREATE TABLE downloads (
	downloads_id INTEGER PRIMARY KEY AUTOINCREMENT,
	id_key UUID,
	u_key UUID
);

const PREFIX = 'agriflow.cache.';

function key(name) {
    return `${PREFIX}${name}`;
}

export function readCachedList(name) {
    try {
        const raw = window.localStorage.getItem(key(name));

        if (!raw) {
            return [];
        }

        const parsed = JSON.parse(raw);
        return Array.isArray(parsed) ? parsed : [];
    } catch {
        return [];
    }
}

export function writeCachedList(name, rows) {
    window.localStorage.setItem(key(name), JSON.stringify(Array.isArray(rows) ? rows : []));
}

export function upsertCachedRow(name, row, rowIdKey = 'uuid') {
    const rows = readCachedList(name);
    const id = row?.[rowIdKey];

    if (!id) {
        rows.unshift(row);
        writeCachedList(name, rows);
        return rows;
    }

    const index = rows.findIndex((item) => item?.[rowIdKey] === id);

    if (index === -1) {
        rows.unshift(row);
    } else {
        rows[index] = { ...rows[index], ...row };
    }

    writeCachedList(name, rows);
    return rows;
}

export function removeCachedRow(name, rowId, rowIdKey = 'uuid') {
    const rows = readCachedList(name).filter((item) => item?.[rowIdKey] !== rowId);
    writeCachedList(name, rows);
    return rows;
}

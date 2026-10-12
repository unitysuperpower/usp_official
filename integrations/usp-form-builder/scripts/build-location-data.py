"""Build compact country files from CSC country/state JSON and gzipped city JSON.
Usage: python3 scripts/build-location-data.py countries.json states.json cities.json.gz
Data by https://github.com/dr5hn/countries-states-cities-database, ODbL-1.0.
"""
import gzip, hashlib, json, sys
from pathlib import Path
from collections import defaultdict
countries_path, states_path, cities_path = map(Path, sys.argv[1:])
countries = json.loads(countries_path.read_text())
states = {s['id']: s for s in json.loads(states_path.read_text())}
country_names = {c['iso2']: c['name'] for c in countries}
regions = defaultdict(dict)

def root_state(state_id):
    visited = set()
    while state_id in states:
        if state_id in visited:
            raise ValueError('Circular region hierarchy')
        visited.add(state_id)
        parent = states[state_id].get('parent_id')
        if not parent or parent not in states:
            return state_id
        if states[parent]['country_code'] != states[state_id]['country_code']:
            raise ValueError('Cross-country region hierarchy')
        state_id = parent
    return state_id

for state in states.values():
    root_id = root_state(state['id'])
    root = states[root_id]
    regions[root['country_code']].setdefault(str(root_id), {'id': str(root_id), 'name': root['name'], 'cities': set()})

def stream_objects(path):
    decoder = json.JSONDecoder()
    with gzip.open(path, 'rt', encoding='utf-8') as source:
        buffer = ''
        finished = False
        while not finished:
            chunk = source.read(1024 * 1024)
            finished = not chunk
            buffer += chunk
            while True:
                buffer = buffer.lstrip(' \r\n\t[,')
                if not buffer or buffer.startswith(']'):
                    break
                try:
                    obj, end = decoder.raw_decode(buffer)
                except json.JSONDecodeError:
                    if finished:
                        raise
                    break
                yield obj
                buffer = buffer[end:]

city_count = 0
for city in stream_objects(cities_path):
    code = city['country_code']
    if code not in country_names:
        raise ValueError('Unknown country: ' + code)
    root_id = root_state(city.get('state_id'))
    key = str(root_id) if root_id is not None else '_country'
    region = regions[code].setdefault(key, {'id': key, 'name': city.get('state_name') or country_names[code], 'cities': set()})
    region['cities'].add(city['name'])
    city_count += 1
out = Path(__file__).resolve().parents[1] / 'assets' / 'locations'
for code, name in sorted(country_names.items()):
    rows = []
    for region in sorted(regions[code].values(), key=lambda s: s['name'].casefold()):
        rows.append({**region, 'cities': sorted(region['cities'], key=str.casefold)})
    (out / (code + '.json')).write_text(json.dumps({'country': code, 'name': name, 'states': rows}, ensure_ascii=False, separators=(',', ':')) + '\n')
(out / 'countries.json').write_text(json.dumps(country_names, ensure_ascii=False, sort_keys=True, separators=(',', ':')) + '\n')
metadata = {'source': 'https://github.com/dr5hn/countries-states-cities-database', 'license': 'ODbL-1.0', 'snapshot_date': '2026-10-12', 'countries': len(country_names), 'regions': sum(map(len, regions.values())), 'source_city_records': city_count, 'unique_city_choices': sum(len(s['cities']) for rows in regions.values() for s in rows.values()), 'source_sha256': {p.name: hashlib.sha256(p.read_bytes()).hexdigest() for p in [countries_path, states_path, cities_path]}}
(out / 'metadata.json').write_text(json.dumps(metadata, indent=2) + '\n')
print(json.dumps(metadata, indent=2))

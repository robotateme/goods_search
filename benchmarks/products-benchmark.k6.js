import http from 'k6/http';
import { check } from 'k6';

export const options = {
  scenarios: {
    database_listing: {
      executor: 'constant-vus',
      exec: 'databaseListing',
      vus: 5,
      duration: '30s',
      tags: { benchmark: 'database_listing' },
    },
    filtered_listing: {
      executor: 'constant-vus',
      exec: 'filteredListing',
      vus: 5,
      duration: '30s',
      tags: { benchmark: 'filtered_listing' },
    },
    fulltext_listing: {
      executor: 'constant-vus',
      exec: 'fulltextListing',
      vus: 5,
      duration: '30s',
      tags: { benchmark: 'fulltext_listing' },
    },
  },
  thresholds: {
    'http_req_failed{benchmark:database_listing}': ['rate<0.01'],
    'http_req_failed{benchmark:filtered_listing}': ['rate<0.01'],
    'http_req_failed{benchmark:fulltext_listing}': ['rate<0.01'],
    'http_req_duration{benchmark:database_listing}': ['p(95)<300'],
    'http_req_duration{benchmark:filtered_listing}': ['p(95)<400'],
    'http_req_duration{benchmark:fulltext_listing}': ['p(95)<500'],
  },
};

const baseUrl = __ENV.BASE_URL || 'http://localhost/api/products';

function request(url, tags) {
  const response = http.get(url, {
    headers: {
      Accept: 'application/json',
    },
    tags,
  });

  check(response, {
    'status is 200': (r) => r.status === 200,
    'has data array': (r) => Array.isArray(r.json('data')),
  });
}

export function databaseListing() {
  request(`${baseUrl}?page=1&per_page=20`, {
    benchmark: 'database_listing',
    driver: __ENV.SEARCH_DRIVER || 'database',
    cache: __ENV.SEARCH_CACHE || 'unknown',
  });
}

export function filteredListing() {
  request(
    `${baseUrl}?category_id=1&in_stock=true&price_from=50&price_to=250&rating_from=4&page=1&per_page=20`,
    {
      benchmark: 'filtered_listing',
      driver: __ENV.SEARCH_DRIVER || 'database',
      cache: __ENV.SEARCH_CACHE || 'unknown',
    },
  );
}

export function fulltextListing() {
  request(`${baseUrl}?q=Mouse&sort=price_desc&page=1&per_page=20`, {
    benchmark: 'fulltext_listing',
    driver: __ENV.SEARCH_DRIVER || 'database',
    cache: __ENV.SEARCH_CACHE || 'unknown',
  });
}

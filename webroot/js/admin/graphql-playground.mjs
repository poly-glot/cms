import { postJson } from './kit/http.mjs';

const { React, ReactDOM, GraphiQL } = window;
const mountEl = document.querySelector('[data-graphiql]');

const EXAMPLES = [
    {
        label: 'Published posts',
        query: `{
  posts(perPage: 5) {
    items { title slug status publishedAt tags { slug label } }
    pageInfo { total hasNextPage }
  }
}`,
    },
    {
        label: 'Page by path — with custom fields',
        query: `{
  page(path: "about") {
    title
    fullPath
    body
    customFields
    fieldSchema { name label type }
  }
}`,
    },
    {
        label: 'Collection entries',
        query: `{
  collection(slug: "profile") {
    name
    fieldSchema { name label type }
    entries(perPage: 10) { items { title slug data } pageInfo { total } }
  }
}`,
    },
    {
        label: 'Navigation menu',
        query: `{
  menu(slug: "main") {
    name
    items { title url target page { fullPath } children { title url } }
  }
}`,
    },
    {
        label: 'Preview a draft — needs a token with preview scope',
        query: `{
  posts(perPage: 5) {
    items { title slug status }
  }
}`,
    },
    {
        label: 'Create a post — needs a token with write scope',
        query: `mutation {
  createPost(input: { title: "Hello from the playground", slug: "playground-hello", status: DRAFT, body: "<p>Hi</p>" }) {
    id slug status author { name }
  }
}`,
    },
];

if (mountEl && React && ReactDOM && GraphiQL) {
    const { useState, useCallback, createElement: h, Fragment } = React;

    const { endpoint, tokenEndpoint } = mountEl.dataset;
    const fetcher = GraphiQL.createFetcher({ url: endpoint });

    function Console() {
        const [query, setQuery] = useState(EXAMPLES[0].query);
        const [headers, setHeaders] = useState('{}');
        const [note, setNote] = useState('');
        const [busy, setBusy] = useState(false);

        const loadExample = useCallback((value) => {
            if (value !== '') {
                setQuery(EXAMPLES[Number(value)].query);
            }
        }, []);

        const generateToken = useCallback(async () => {
            setBusy(true);
            setNote('Generating…');

            try {
                const data = await postJson(tokenEndpoint, {});

                if (typeof data.token === 'string') {
                    setHeaders(JSON.stringify({ Authorization: `Bearer ${data.token}` }, null, 2));
                    setNote('Temporary token added to Headers — read, preview & write, expires in 1 hour.');
                } else {
                    setNote('Could not generate a token.');
                }
            } catch {
                setNote('Could not generate a token.');
            } finally {
                setBusy(false);
            }
        }, []);

        return h(
            Fragment,
            null,
            h(
                'div',
                { className: 'cms-gql-bar' },
                h(
                    'label',
                    { className: 'cms-gql-bar__field' },
                    'Examples',
                    h(
                        'select',
                        { onChange: (event) => loadExample(event.target.value), value: '' },
                        h('option', { value: '' }, 'Load an example…'),
                        EXAMPLES.map((example, index) =>
                            h('option', { key: index, value: String(index) }, example.label),
                        ),
                    ),
                ),
                h(
                    'button',
                    { type: 'button', className: 'cms-btn', onClick: generateToken, disabled: busy },
                    busy ? 'Generating…' : 'Generate temporary token',
                ),
                note !== '' ? h('span', { className: 'cms-gql-bar__note' }, note) : null,
            ),
            h(
                'div',
                { className: 'cms-gql-frame' },
                h(GraphiQL, {
                    fetcher,
                    query,
                    onEditQuery: setQuery,
                    headers,
                    onEditHeaders: setHeaders,
                    defaultEditorToolsVisibility: 'headers',
                }),
            ),
        );
    }

    ReactDOM.createRoot(mountEl).render(h(Console));
}

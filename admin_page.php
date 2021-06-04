
<div class="wrap">
    <label>URL: </label><input type="text" id="url-rss">
    <button onclick="loadRssUrl()">Carregar</button>
    <div id="rss-importer">
</div>
<link href="https://unpkg.com/tabulator-tables@4.9.3/dist/css/tabulator.min.css" rel="stylesheet">
<script type="text/javascript" src="https://unpkg.com/tabulator-tables@4.9.3/dist/js/tabulator.min.js"></script>
<script>

    var get_links_from_rows = rows => rows.map(row => row._row.data.link);

    var send_links_ajax = () => {
        jQuery.post('<?=$ajax_url?>', {
            'action' : 'rssimporter_ajax_add_urls',
            'urls'   : get_links_from_rows(table.getSelectedRows()),
        }, () => { alert('Sucesso!'); });
    }

    var table = new Tabulator("#rss-importer", {
        selectable : true,
        columns : [
            { formatter : 'rowSelection', titleFormatter : 'rowSelection', align : 'center', headerSort : false },
            { title : 'Data', field : 'pubDate', sorter : 'string' },
            { title : 'Título', field : 'title', sorter : 'string' },
            { title : 'Link', field : 'link', sorter : 'string' },
        ],
        footerElement : "<button onclick='send_links_ajax()'>Adicionar Selecionados</button>",
    });

    function loadRssUrl(url) {
        table.setData('<?=$ajax_url?>', { 'action' : 'rssimporter_ajax_rss_to_json', 'url' : document.getElementById('url-rss').value });
    }

</script>

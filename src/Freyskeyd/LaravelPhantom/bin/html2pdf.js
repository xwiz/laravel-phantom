// ===============================
//            ARGUMENTS
// ===============================
// system.args[1] => body
// system.args[2] => header
// system.args[3] => footer
// system.args[4] => output
// system.args[5] => header height
// system.args[6] => footer height
// 8.8 4.3
// ===============================

var page        = new WebPage(),
	system      = require('system'),
	file_system = require('fs');


/*
	Change paper size to A4, add borders
	Add page numbers in footer
 */
page.paperSize = {
	format: 'A4',
	orientation: 'portrait',
	border: '20px',
	header: {
		height: system.args[5] + 'cm',
		contents: phantom.callback(function (pageNum, numPages) {
			var html = '';

			if( file_system.isReadable( system.args[2] ) )
			{
				var file_contents = file_system.open( system.args[2], 'r' );
				html = file_contents.read();
			}

			return html;
		})
	},
	footer: {
		height: system.args[6] + 'cm',
		contents: phantom.callback(function (pageNum, numPages) {
			var html = '';

			if( file_system.isReadable( system.args[3] ) )
			{
				var file_contents = file_system.open( system.args[3], 'r' );
				html = file_contents.read();
			}

			if( html === '' ) {
				return '<div style="text-align: center;"><small>' + pageNum + '/' + numPages + '</small></div>';
			}
			else {
				var insert = pageNum + '/' + numPages;
				html = html.replace('##page_number##', insert);
				return html;
			}
		})
	}
};


page.zoomFactor = 1.0;


page.open(system.args[1], function(status) {
	console.log('Status', status);
	console.log('Render to ' + system.args[4]);

	window.setTimeout(function() {
		page.render(system.args[4]);
		phantom.exit();
	}, 1000);
});

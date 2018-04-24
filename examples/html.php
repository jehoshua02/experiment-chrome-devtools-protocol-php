<?php

require_once __DIR__ . '/../vendor/autoload.php';

use ChromeDevtoolsProtocol\Context;
use ChromeDevtoolsProtocol\Instance\Launcher;
use ChromeDevtoolsProtocol\Model\Page\PrintToPDFRequest;
use ChromeDevtoolsProtocol\Model\Page\SetDocumentContentRequest;

@unlink(__DIR__ . '/../test.pdf');

// context creates deadline for operations
$ctx = Context::withTimeout(Context::background(), 30 /* seconds */);

// launcher starts chrome process ($instance)
$launcher = new Launcher();
$instance = $launcher->launch($ctx);

try {
	// work with new tab
	$tab = $instance->open($ctx);
	$tab->activate($ctx);

	$devtools = $tab->devtools();
	try {
        // instantiate devtools
        $devtools->page()->enable($ctx);

        // build custom html
        $html = '<!DOCTYPE html><html><head></head><body><h1>Hello World</h1>' . str_repeat('<div>Just a div</div>', 100) . '</body></html>';

        // set document content, requires a frame id (that was fun to figure out)
        $devtools->page()->setDocumentContent($ctx, SetDocumentContentRequest::fromJson((object) [
            'frameId' => $devtools->page()->getFrameTree($ctx)->frameTree->frame->id,
            'html' => $html
        ]));

        // wait for page to load (necessary?)
        $devtools->page()->awaitLoadEventFired($ctx);

        // get pdf content
        $data = $devtools->page()->printToPDF($ctx, PrintToPDFRequest::fromJson((object) [
            'displayHeaderFooter' => false
        ]))->data;

        // save pdf content
        file_put_contents(__DIR__ . '/../test.pdf', base64_decode($data));
	} finally {
		// devtools client needs to be closed
		$devtools->close();
	}

} finally {
	// process needs to be killed
	$instance->close();
}
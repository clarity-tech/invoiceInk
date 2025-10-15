const http = require('node:http');
const puppeteer = require('puppeteer');

const server = http.createServer(async (req, res) => {
  // Set CORS headers
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'POST, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type');
  
  // Handle OPTIONS requests
  if (req.method === 'OPTIONS') {
    res.writeHead(200);
    res.end();
    return;
  }
  
  // Handle POST requests to /generate-pdf
  if (req.method === 'POST' && req.url === '/generate-pdf') {
    try {
      const chunks = [];
      
      // Collect request body chunks
      req.on('data', (chunk) => {
        chunks.push(chunk);
      });
      
      // Process when all data received
      req.on('end', async () => {
        try {
          // Parse JSON from request body
          const bodyData = Buffer.concat(chunks).toString();
          const { html, options = {} } = JSON.parse(bodyData);
          
          if (!html) {
            res.writeHead(400, { 'Content-Type': 'application/json' });
            res.end(JSON.stringify({ error: 'HTML content is required' }));
            return;
          }
          
          console.log('Generating PDF for HTML content length:', html.length);
          
          // Launch Puppeteer
          const browser = await puppeteer.launch({
            headless: true,
            args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage']
          });
          
          const page = await browser.newPage();
          await page.setContent(html, { waitUntil: 'networkidle0' });
          
          // PDF generation options
          const pdfOptions = {
            format: 'A4',
            margin: { top: '10mm', right: '10mm', bottom: '10mm', left: '10mm' },
            printBackground: true,
            ...options
          };
          
          // Generate PDF
          const pdfBuffer = await page.pdf(pdfOptions);
          await browser.close();
          
          console.log('PDF generated successfully, size:', pdfBuffer.length, 'bytes');
          
          // Send PDF response
          res.writeHead(200, {
            'Content-Type': 'application/pdf',
            'Content-Length': pdfBuffer.length
          });
          res.end(pdfBuffer);
          
        } catch (error) {
          console.error('PDF generation error:', error);
          res.writeHead(500, { 'Content-Type': 'application/json' });
          res.end(JSON.stringify({ error: 'PDF generation failed: ' + error.message }));
        }
      });
      
    } catch (error) {
      console.error('Request processing error:', error);
      res.writeHead(500, { 'Content-Type': 'application/json' });
      res.end(JSON.stringify({ error: 'Internal server error: ' + error.message }));
    }
  } else if (req.method === 'GET' && req.url === '/health') {
    // Health check endpoint
    res.writeHead(200, { 'Content-Type': 'application/json' });
    res.end(JSON.stringify({ status: 'healthy', timestamp: new Date().toISOString() }));
  } else {
    // 404 for other routes
    res.writeHead(404, { 'Content-Type': 'application/json' });
    res.end(JSON.stringify({ error: 'Not found' }));
  }
});

// Handle server errors
server.on('error', (error) => {
  console.error('Server error:', error);
});

server.listen(3000, () => {
  console.log('PDF service listening on port 3000');
  console.log('Health check: http://localhost:3000/health');
  console.log('PDF generation: POST http://localhost:3000/generate-pdf');
});
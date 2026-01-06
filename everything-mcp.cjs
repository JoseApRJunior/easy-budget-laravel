const { spawn } = require('child_process');
const readline = require('readline');

const EVERYTHING_PATH = 'C:\\Users\\junio\\AppData\\Local\\Microsoft\\WindowsApps\\es.exe';

const rl = readline.createInterface({
  input: process.stdin,
  output: process.stdout,
  terminal: false
});

function log(msg) {
  // Não envia nada para stdout que não seja JSON RPC
}

function sendResponse(id, result) {
  const response = JSON.stringify({
    jsonrpc: '2.0',
    id,
    result
  });
  process.stdout.write(response + '\n');
}

rl.on('line', (line) => {
  if (!line.trim()) return;
  try {
    const request = JSON.parse(line);
    const { method, params, id } = request;

    if (method === 'initialize') {
      return sendResponse(id, {
        protocolVersion: '2024-11-05',
        capabilities: { tools: {} },
        serverInfo: { name: 'everything-search', version: '1.1.0' }
      });
    }

    if (method === 'tools/list') {
      return sendResponse(id, {
        tools: [
          {
            name: 'search',
            description: 'Busca arquivos e pastas no projeto',
            inputSchema: {
              type: 'object',
              properties: {
                query: { type: 'string' },
                limit: { type: 'number', default: 20 }
              },
              required: ['query']
            }
          }
        ]
      });
    }

    if (method === 'tools/call') {
      if (params.name === 'search') {
        const query = `C:\\laragon\\www\\easy-budget-laravel ${params.arguments.query}`;
        const limit = params.arguments.limit || 20;

        const child = spawn(EVERYTHING_PATH, [query, '-n', limit.toString()], { shell: true });
        let output = '';

        child.stdout.on('data', (data) => { output += data.toString(); });
        child.on('close', () => {
          sendResponse(id, {
            content: [{ type: 'text', text: output.trim() || 'Nenhum resultado encontrado.' }]
          });
        });
        return;
      }
    }

    if (id) sendResponse(id, { content: [] });
  } catch (err) {
    // Silencioso para não gerar "Emergency message"
  }
});

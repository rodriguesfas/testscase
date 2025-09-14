const readline = require('readline');

const rl = readline.createInterface({
    input: process.stdin,
    output: process.stdout
});

let lines = [];
rl.on('line', (line) => {
    lines.push(line);
});

rl.on('close', () => {
    const N = parseInt(lines[0]);
    const alturas = lines[1].split(' ').map(Number);
    
    let substituidos = 0;
    let consertados = 0;
    
    for (let i = 0; i < N; i++) {
        if (alturas[i] < 50) {
            substituidos++;
        }
        if (alturas[i] >= 50 && alturas[i] < 85) {
            consertados++;
        }
    }
    
    console.log(substituidos + ' ' + consertados);
});

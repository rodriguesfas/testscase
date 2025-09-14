#include <iostream>
using namespace std;

int main() {
    int N;
    cin >> N;
    
    int substituidos = 0;
    int consertados = 0;
    
    for (int i = 0; i < N; i++) {
        int altura;
        cin >> altura;
        
        if (altura < 50) {
            substituidos++;
        }
        if (altura >= 50 && altura < 85) {
            consertados++;
        }
    }
    
    cout << substituidos << " " << consertados << endl;
    
    return 0;
}

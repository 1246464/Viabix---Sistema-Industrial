# Integração Dashboard Viabilidade - Android

## ✅ O que foi criado

Implementei a integração do novo endpoint `/api/dashboard_viabilidade_simple.php` no app Android Viabix.

### Arquivos criados em `viabix-android/app/src/main/java/com/viabix/app/`:

1. **presentation/screens/viabilidade/ViabilidadeUiState.kt**
   - Data classes para os dados de viabilidade
   - Estrutura: `AnviData`, `AnaliseData`, `ViabilidadeData`, `Compatibilidade`

2. **presentation/screens/viabilidade/ViabilidadeViewModel.kt**
   - ViewModel que faz a requisição ao novo endpoint
   - Gerencia estados de loading/error
   - Função: `loadViabilidadeData(anviId: String)`

3. **presentation/screens/viabilidade/ViabilidadeScreens.kt**
   - UI completa com Jetpack Compose + Material Design 3
   - 5 cards detalhados:
     - Input para ANVI ID
     - Informações do ANVI
     - Score de Viabilidade Geral (92.1/100 com status)
     - Análise Detalhada (Financeiro, Planejamento, Qualidade, Recursos)
     - Compatibilidades por Área
   - Tema verde (#2ecc71) padrão VIABIX

4. **data/api/RetrofitClient.kt**
   - Atualizado com novo endpoint
   - Interface: `ViabixApiServiceWithViabilidade`
   - Inclui: login, anvi_list, dashboard_viabilidade

5. **data/api/ViabilidadeApiService.kt**
   - Interface específica para endpoints de viabilidade
   - Função helper para obter o serviço

## 🔧 Como integrar ao projeto existente

### Passo 1: Copiar os arquivos criados
Os arquivos já estão em:
```
c:\xampp\htdocs\ANVI\viabix-android\app\src\main\java\com\viabix\app\
```

### Passo 2: Atualizar a interface ViabixApiService
Se você já tem um `ViabixApiService.kt` existente, adicione este método:

```kotlin
@GET("api/dashboard_viabilidade_simple.php")
suspend fun getDashboardViabilidade(
    @Query("anvi_id") anviId: String
): Response<DashboardViabilidadeResponse>
```

### Passo 3: Atualizar RetrofitClient.kt
Certifique-se que o `getApiService()` retorna a interface correta que inclui o novo endpoint.

### Passo 4: Atualizar Navigation
Adicione a rota no seu `NavHost.kt`:

```kotlin
composable<Screen.ViabilidadeDashboard> { backStackEntry ->
    val viabilidadeViewModel: ViabilidadeViewModel = hiltViewModel()
    ViabilidadeDashboardScreen(
        viewModel = viabilidadeViewModel,
        onNavigateBack = { navController.popBackStack() }
    )
}
```

### Passo 5: Compilar e Testar

```bash
cd viabix-android
./gradlew clean build
./gradlew assembleDebug
```

## 📱 Como testar no app

1. Faça login normalmente
2. Navegue para o Dashboard de Viabilidade
3. Digite um ANVI ID válido: `ANVI-20260509234642-001`
4. Clique em "Carregar Análise"
5. Os dados reais virão do servidor

## 🎨 Features implementadas

✅ Requisição real ao endpoint `/api/dashboard_viabilidade_simple.php`  
✅ Layout responsivo com Jetpack Compose  
✅ Tratamento de erros e loading states  
✅ Tema verde padrão VIABIX  
✅ Cards com dados de análise detalhada  
✅ Scores visuais com círculos coloridos  
✅ Compatibilidades por área com ícones  
✅ Recomendações personalizadas  

## 🔐 Autenticação

O app já usa JWT token (via `TokenManager`) que é adicionado automaticamente em todas as requisições:
- Header: `Authorization: Bearer <token>`
- O endpoint funcionará apenas para usuários autenticados

## 📊 Dados exibidos

```
📋 ANVI
- ID, Número, Revisão, Cliente, Projeto, Status, Data

👍 Viabilidade Geral  
- Score (0-100)
- Status (VIÁVEL/INVIÁVEL)
- Recomendação

🔬 Análise Detalhada
- Financeiro: Investimento, ROI, Payback
- Planejamento: Fases, Duração
- Qualidade: Cobertura de Testes, Score Código
- Recursos: Equipe, Especialistas

✅ Compatibilidades
- Por Área (Financeiro, Planejamento, Qualidade, Recursos)
- Status (Compatível/Incompatível)
- Detalhes
```

## 🚀 Próximos passos opcionais

1. **Cache offline**: Implementar Room Database para cachear dados
2. **Refresh**: Adicionar pull-to-refresh
3. **Detalhes**: Clicar em um card para ver mais informações
4. **Histórico**: Mostrar múltiplos ANVIs

---

**Data de criação**: 09/05/2026  
**Status**: Pronto para compilar e testar  
**Endpoint**: https://viabix.com.br/api/dashboard_viabilidade_simple.php

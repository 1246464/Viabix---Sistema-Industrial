package com.viabix.app

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.rememberNavController
import com.viabix.app.presentation.screens.home.HomeScreen
import com.viabix.app.presentation.screens.login.LoginScreen
import com.viabix.app.presentation.screens.login.LoginViewModel
import com.viabix.app.presentation.screens.viabilidade.ViabilidadeDashboardScreen
import com.viabix.app.presentation.screens.webview.WebViewScreen
import com.viabix.app.ui.theme.ViabixTheme
import dagger.hilt.android.AndroidEntryPoint
import androidx.hilt.navigation.compose.hiltViewModel

@AndroidEntryPoint
class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContent {
            ViabixTheme {
                Surface(
                    modifier = Modifier.fillMaxSize(),
                    color = MaterialTheme.colorScheme.background
                ) {
                    AppNavigation()
                }
            }
        }
    }
}

@Composable
fun AppNavigation() {
    val navController = rememberNavController()
    // ViewModel compartilhado para gerenciar o estado global de autenticação
    val loginViewModel: LoginViewModel = hiltViewModel()

    NavHost(
        navController = navController,
        startDestination = "login"
    ) {
        composable("login") {
            LoginScreen(
                viewModel = loginViewModel,
                onLoginSuccess = {
                    navController.navigate("home") {
                        popUpTo("login") { inclusive = true }
                    }
                }
            )
        }

        composable("home") {
            HomeScreen(
                onLogout = {
                    // Executa o logout e navega limpando todo o histórico
                    loginViewModel.logout()
                    navController.navigate("login") {
                        popUpTo(0) { inclusive = true }
                        launchSingleTop = true
                    }
                },
                onNavigateToViabilidade = {
                    navController.navigate("viabilidade")
                },
                onNavigateToANVI = {
                    navController.navigate("anvi")
                },
                onNavigateToProjetos = {
                    navController.navigate("projetos")
                },
                onNavigateToFaturamento = {
                    navController.navigate("faturamento")
                }
            )
        }

        composable("viabilidade") {
            ViabilidadeDashboardScreen(
                onNavigateBack = { navController.popBackStack() }
            )
        }

        composable("anvi") {
            WebViewScreen(
                title = "ANVI",
                url = "https://viabix.com.br/anvi.html",
                onNavigateBack = { navController.popBackStack() }
            )
        }

        composable("projetos") {
            WebViewScreen(
                title = "Controle de Projetos",
                url = "https://viabix.com.br/Controle_de_projetos/",
                onNavigateBack = { navController.popBackStack() }
            )
        }

        composable("faturamento") {
            WebViewScreen(
                title = "Faturamento e SaaS",
                url = "https://viabix.com.br/billing.html",
                onNavigateBack = { navController.popBackStack() }
            )
        }
    }
}

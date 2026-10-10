package com.viabix.app

import android.net.Uri
import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.hilt.navigation.compose.hiltViewModel
import androidx.navigation.NavType
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.rememberNavController
import androidx.navigation.navArgument
import com.viabix.app.presentation.screens.anvis.AnviListScreen
import com.viabix.app.presentation.screens.anvis.AnviFormScreen
import com.viabix.app.presentation.screens.billing.BillingScreen
import com.viabix.app.presentation.screens.home.HomeScreen
import com.viabix.app.presentation.screens.login.LoginScreen
import com.viabix.app.presentation.screens.login.LoginViewModel
import com.viabix.app.presentation.screens.projects.ProjectsScreen
import com.viabix.app.presentation.screens.projects.ProjectFormScreen
import com.viabix.app.presentation.screens.viabilidade.ViabilidadeDashboardScreen
import com.viabix.app.ui.theme.ViabixTheme
import dagger.hilt.android.AndroidEntryPoint

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
    val loginViewModel: LoginViewModel = hiltViewModel()

    NavHost(navController = navController, startDestination = "login") {
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
                    loginViewModel.logout()
                    navController.navigate("login") {
                        popUpTo(0) { inclusive = true }
                        launchSingleTop = true
                    }
                },
                onNavigateToViabilidade = { navController.navigate("viabilidade") },
                onNavigateToANVI = { navController.navigate("anvis") },
                onNavigateToProjetos = { navController.navigate("projetos") },
                onNavigateToFaturamento = { navController.navigate("faturamento") },
                onNavigateAction = { legacyAction ->
                    when {
                        legacyAction.contains("Controle_de_projetos", ignoreCase = true) -> navController.navigate("projetos")
                        legacyAction.contains("billing", ignoreCase = true) -> navController.navigate("faturamento")
                        legacyAction.contains("dashboard_viabilidade", ignoreCase = true) -> {
                            val anviId = Uri.parse(legacyAction).getQueryParameter("anvi_id")
                            if (anviId.isNullOrBlank()) {
                                navController.navigate("viabilidade")
                            } else {
                                navController.navigate("viabilidade/${Uri.encode(anviId)}")
                            }
                        }
                        legacyAction.contains("anvi", ignoreCase = true) -> navController.navigate("anvis")
                    }
                }
            )
        }

        composable("anvis") {
            AnviListScreen(
                onNavigateBack = { navController.popBackStack() },
                onOpenAnvi = { anviId -> navController.navigate("viabilidade/${Uri.encode(anviId)}") },
                onCreateAnvi = { navController.navigate("anvi-form") },
                onEditAnvi = { anviId -> navController.navigate("anvi-form/${Uri.encode(anviId)}") }
            )
        }

        composable("anvi-form") {
            AnviFormScreen(
                onNavigateBack = { navController.popBackStack() },
                onSaved = {
                    navController.navigate("anvis") {
                        popUpTo("anvis") { inclusive = true }
                    }
                }
            )
        }

        composable(
            route = "anvi-form/{anviId}",
            arguments = listOf(navArgument("anviId") { type = NavType.StringType })
        ) {
            AnviFormScreen(
                onNavigateBack = { navController.popBackStack() },
                onSaved = {
                    navController.navigate("anvis") {
                        popUpTo("anvis") { inclusive = true }
                    }
                }
            )
        }

        composable("projetos") {
            ProjectsScreen(
                onNavigateBack = { navController.popBackStack() },
                onCreateProject = { navController.navigate("project-form") },
                onEditProject = { projectId -> navController.navigate("project-form/$projectId") }
            )
        }

        composable("project-form") {
            ProjectFormScreen(
                onNavigateBack = { navController.popBackStack() },
                onSaved = {
                    navController.navigate("projetos") {
                        popUpTo("projetos") { inclusive = true }
                    }
                }
            )
        }

        composable(
            route = "project-form/{projectId}",
            arguments = listOf(navArgument("projectId") { type = NavType.StringType })
        ) {
            ProjectFormScreen(
                onNavigateBack = { navController.popBackStack() },
                onSaved = {
                    navController.navigate("projetos") {
                        popUpTo("projetos") { inclusive = true }
                    }
                }
            )
        }

        composable("faturamento") {
            BillingScreen(onNavigateBack = { navController.popBackStack() })
        }

        composable("viabilidade") {
            ViabilidadeDashboardScreen(onNavigateBack = { navController.popBackStack() })
        }

        composable(
            route = "viabilidade/{anviId}",
            arguments = listOf(navArgument("anviId") { type = NavType.StringType })
        ) { backStackEntry ->
            ViabilidadeDashboardScreen(
                initialAnviId = Uri.decode(backStackEntry.arguments?.getString("anviId").orEmpty()),
                onNavigateBack = { navController.popBackStack() }
            )
        }
    }
}

/**
 *   Jenkins build script for HC Web/Mobile framework with laminas
 *
 */

pipeline {
    agent {
        label 'StandardV1'
    }
    options {
        // This is required if you want to clean before build
        skipDefaultCheckout(true)
    }
    environment {
        containerRegistry = 'jack.hc-sc.gc.ca'
        containerRegistryPull = 'jack.hc-sc.gc.ca'
    }

    stages {
        stage('appmeta Info') {
            steps {
                // Clean before build
                cleanWs()
                // We need to explicitly checkout from SCM here
                checkout scm
                script {

                    def properties = readProperties  file: 'appmeta.properties'

                    //Get basic meta-data
                    rootGroup = properties.root_group
                    rootVersion = properties.root_version
                    buildId = env.BUILD_ID
                    version = rootVersion + "b" + (buildId ? buildId : "MANUAL-BUILD")
                    module = rootGroup

                    // Setup Artifactory connection
                    artifactoryServer = Artifactory.server 'default'
                    artifactoryGradle = Artifactory.newGradleBuild()
                    artifactoryDocker = Artifactory.docker server: artifactoryServer
                    buildInfo = Artifactory.newBuildInfo()
                }
            }
        }

        stage('Docker Image') {
            when {
                expression {
                    BRANCH_NAME == 'master'
                }
            }
            steps {
                sh 'rm -rf ./docker/code'
                sh 'cp -r ./code ./docker'

                withCredentials([
                    usernamePassword(credentialsId:'ARTIFACTORY_PUBLISH', usernameVariable: 'USR', passwordVariable: 'PWD')
                ]) {
                    sh """
                        docker login -u ${USR} -p ${PWD} ${
                            containerRegistry
                        }
                        docker build -t php-base-laminas:${version} -t php-base-laminas:latest .
                        docker tag php-base-laminas:${version} ${containerRegistry}/php/php-base-laminas:${version}
                        docker tag php-base-laminas:latest ${containerRegistry}/php/php-base-laminas:latest

                        docker build -t php-base-laminas:${version}-mongodb -t php-base-laminas:latest-mongodb . -f dockerfile-mongodb
                        docker tag php-base-laminas:${version}-mongodb ${containerRegistry}/php/php-base-laminas:${version}-mongodb
                        docker tag php-base-laminas:latest-mongodb ${containerRegistry}/php/php-base-laminas:latest-mongodb

                    """
                }
                script {
                    def buildInfoTemp
                    buildInfoTemp = artifactoryDocker.push "${containerRegistry}/php/php-base-laminas:${version}", 'docker-local'
                    buildInfo.append buildInfoTemp
                    buildInfoTemp = artifactoryDocker.push "${containerRegistry}/php/php-base-laminas:latest", 'docker-local'
                    buildInfo.append buildInfoTemp
                    buildInfoTemp = artifactoryDocker.push "${containerRegistry}/php/php-base-laminas:${version}-mongodb", 'docker-local'
                    buildInfo.append buildInfoTemp
                    buildInfoTemp = artifactoryDocker.push "${containerRegistry}/php/php-base-laminas:latest-mongodb", 'docker-local'
                    buildInfo.append buildInfoTemp
                }
            }
        }
    }

    post {
        always {
            // Clean after build
            cleanWs()
            script {
                resultString = "None"
            }
        }
        success {
            script {
                resultString = "Success ☼"
            }
        }
        unstable {
            script {
                resultString = "Unstable ⛅"
            }
        }
        failure {
            script {
                resultString = "Failure 🌩"
            }
        }
        cleanup {
            emailext body: "<strong>${resultString}</strong><p>See build result details at: <a href='${env.JOB_URL}'>${env.JOB_URL}</a></p>", mimeType: 'text/html; charset=UTF-8', recipientProviders: [[$class: 'CulpritsRecipientProvider'], [$class: 'DevelopersRecipientProvider'], [$class: 'UpstreamComitterRecipientProvider'], [$class: 'RequesterRecipientProvider']], replyTo: 'devops@hc-sc.gc.ca', subject: "${resultString} ${currentBuild.fullDisplayName}"
            script {
                jiraIssueSelector(issueSelector: [$class: 'DefaultIssueSelector'])
                        .each {
                    id -> jiraComment body: "*Build Result ${resultString}* Module: ${module} appmeta: ${version} [Details|${env.BUILD_URL}]", issueKey: id
                }
            }
        }
    }
}

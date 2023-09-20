pipeline {
    agent any
    
    environment{
       GITHUB_CLONE_URL = 'git@git.corp.adobe.com:JapanRandD/magento-japan-tax.git'
GITHUB_CLONE_CREDENTIALS = 'takyoshi_github_ssh'
GITHUB_CLONE_ORG = 'JapanRandD'
GITHUB_CLONE_REPO = 'magento-japan-tax'
GITHUB_CLONE_BRANCH = 'main'


       

       EMAIL_ENABLED = true
EMAIL_RECIPIENTS = 'asj-jpnrd-dx@adobe.com'

    }

    stages{
        stage("clean workspace") {
            steps {
                deleteDir()
            }
        }

        //Stage: GitHub Integration
    stage('Clone sources') {
        steps{
            script{
                def gitbranch = "${env.GITHUB_CLONE_BRANCH}"
                if (!env.GITHUB_CLONE_BRANCH) {
                    gitbranch = "origin/develop"
                }else{
                     //to handle issue with origin/BRANCH_NAME
                     def gitbranchOriginSplit = gitbranch.split('origin/')
                     def gitbranchOriginSplitLength = gitbranchOriginSplit.size()
                     gitbranch = gitbranchOriginSplitLength > 1 ? gitbranchOriginSplit[1] : gitbranch
                 }
                print "git branch is ${gitbranch}"
                git credentialsId: "${env.GITHUB_CLONE_CREDENTIALS}", url: "${env.GITHUB_CLONE_URL}", branch: "${gitbranch}"
            }
        }
    }
        
        
        
        
        
        

        
    }
    post {
        failure {
            script {
                currentBuild.result = 'FAILURE'
            }
        }
        always {
            script{
                if(env.EMAIL_ENABLED.toBoolean()){
                    step([$class: 'Mailer',
                        notifyEveryUnstableBuild: true,
                        recipients: "buddhira@adobe.com",
                        sendToIndividuals: true])
                }
            }
        }
    }
}